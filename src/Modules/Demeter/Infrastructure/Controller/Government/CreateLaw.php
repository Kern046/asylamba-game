<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Classes\Library\Parser;
use App\Modules\Athena\Domain\Repository\CommercialTaxRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Manager\SectorManager;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class CreateLaw extends AbstractController
{
	public function __construct(
		private ColorManager                     $colorManager,
		private CommercialTaxRepositoryInterface $commercialTaxRepository,
		private LawManager                       $lawManager,
		private PlayerManager                    $playerManager,
		private PlayerRepositoryInterface        $playerRepository,
		private EntityManagerInterface           $entityManager,
		private SectorRepositoryInterface        $sectorRepository,
		private LawRepositoryInterface           $lawRepository,
		private Parser                           $parser,
	) {
	}

	public function __invoke(
		Request $request,
		Player $currentPlayer,
	): Response {
		$type = $request->query->get('type') ?? throw new BadRequestHttpException('Missing law type');
		$duration = $request->request->get('duration');

		// TODO replace with Voter
		if ($currentPlayer->status !== LawResources::getInfo($type, 'department')) {
			throw $this->createAccessDeniedException('Vous n\' avez pas le droit de proposer cette loi.');
		}

		$faction = $currentPlayer->faction;
		[$statement, $voteEndedAt, $endedAt, $options] =  $this->createLaw($type, $duration, $faction, $currentPlayer, $request);
		$law = new Law(
			id: Uuid::v4(),
			faction: $faction,
			type: $type,
			createdAt: new \DateTimeImmutable(),
			statement: $statement,
			endedAt: $endedAt,
			voteEndedAt: $voteEndedAt,
			options: $options,
		);
		$this->lawRepository->save($law);
		$this->entityManager->flush();

		return $this->redirectToRoute('faction_senate');
	}

	/**
	 * @return array{
	 *     statement: int,
	 *     voteEndedAt: \DateTimeImmutable,
	 *     endedAt: \DateTimeImmutable,
	 *     options: array<string, mixed>,
	 * }
	 * @throws \Exception
	 */
	public function createLaw(float|bool|int|string $type, Law $law, int|null $duration, ?Color $faction, Player $currentPlayer, Request $request): array
	{
		$isRulerLaw = Player::CHIEF === LawResources::getInfo($type, 'department');
		$statement = $isRulerLaw ? Law::EFFECTIVE : Law::VOTATION;

		$voteEndedAt = $isRulerLaw
			? new \DateTimeImmutable()
			: new \DateTimeImmutable('+' . Law::VOTEDURATION . ' second');

		if (LawResources::getInfo($type, 'undeterminedDuration')) {
			$endedAt = new \DateTimeImmutable('+' . 5 . ' years');
		} elseif ($duration) {
			$endedAt = new \DateTimeImmutable('+' . max(1, min($duration, 2400)) . ' hours');
		} else {
			$endedAt = new \DateTimeImmutable();
		}

		if (LawResources::getInfo($type, 'bonusLaw')) {
			$activePlayers = $this->playerRepository->countByFactionAndStatements($faction, [Player::ACTIVE]);
			$lawPrice = LawResources::getInfo($type, 'price') * $duration * $activePlayers;
			if (!$faction->canAfford($lawPrice)) {
				throw new ConflictHttpException('Il n\'y a pas assez de crédits dans les caisses de l\'Etat.');
			}
			$law->options = [];

			if ($this->lawRepository->lawExists($currentPlayer->faction, $type)) {
				throw new ConflictHttpException('Cette loi est déjà proposée ou en vigueur.');
			}

			$this->lawRepository->save($law);
			$faction->credits -= $lawPrice;
			$this->colorManager->sendSenateNotif($faction);

			return [
				'statement' => $statement,
				'voteEndedAt' => $voteEndedAt,
				'endedAt' => $endedAt,
				'options' => [],
			];
		}
		if (!$faction->canAfford(LawResources::getInfo($type, 'price'))) {
			throw new ConflictHttpException('Il n\'y assez pas a de crédits dans les caisses de l\'Etat.');
		}
		$options = [];

		switch ($type) {
			case Law::SECTORTAX:
				$taxes = $request->request->get('taxes')
					?? throw new BadRequestHttpException('Missing taxes amount');
				$rSector = $request->request->get('rsector')
					?? throw new BadRequestHttpException('Missing sector id');
				// TODO replace with specification
				if ($taxes < 2 || $taxes > 15) {
					throw new BadRequestHttpException('La taxe doit être entre 2 et 15 %.');
				}
				$sector = $this->sectorRepository->get(Uuid::fromString($rSector))
					?? throw $this->createNotFoundException('Sector not found');
				// TODO Replace with Voter
				if ($sector->faction->id !== $currentPlayer->faction->id) {
					throw $this->createAccessDeniedException('Ce secteur n\'est pas sous votre contrôle.');
				}
				$options = [
					'taxes' => $taxes,
					'rSector' => $rSector,
					'display' => [
						'Secteur' => $sector->name,
						'Taxe actuelle' => $sector->tax . ' %',
						'Taxe proposée' => $taxes . ' %'
					],
				];
				$faction->credits -= LawResources::getInfo($type, 'price');
				$this->colorManager->sendSenateNotif($faction);
				break;
			case Law::SECTORNAME:
				$rSector = $request->request->get('rsector');
				$name = $request->request->get('name');
				if (false !== $rSector && false !== $name) {
					if (strlen($name) >= 1 and strlen($name) <= 50) {
						$name = $this->parser->protect($name);
						if (($sector = $this->sectorRepository->get($rSector)) !== null) {
							if ($sector->rColor == $currentPlayer->getRColor()) {
								$law->options = serialize(['name' => $name, 'rSector' => $rSector]);
								$this->lawManager->add($law);
								$faction->credits -= LawResources::getInfo($type, 'price');
								$this->colorManager->sendSenateNotif($faction, true);
							} else {
								throw new ErrorException('Ce secteur n\'est pas sous votre contrôle.');
							}
						} else {
							throw new ErrorException('Ce secteur n\'existe pas.');
						}
					} else {
						throw new ErrorException('Le nom doit faire entre 1 et 50 caractères.');
					}
				} else {
					throw new ErrorException('Informations manquantes.');
				}
				break;
			case Law::COMTAXEXPORT:
				$taxes = intval($request->request->get('taxes'));
				$rColor = $request->request->get('rcolor');
				if (false !== $taxes && false !== $rColor) {
					$_CTM = $this->commercialTaxRepository->getCurrentsession();
					$this->commercialTaxRepository->load(['faction' => $currentPlayer->getRColor(), 'relatedFaction' => $rColor]);
					if ($this->commercialTaxRepository->size() > 0) {
						if ($this->commercialTaxRepository->get()->relatedFaction == $currentPlayer->getRColor()) {
							if ($taxes <= 15) {
								$law->options = serialize(['taxes' => $taxes, 'rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => $commercialTaxRepository->get()->exportTax . ' %', 'Taxe proposée' => $taxes . ' %']]);
								$this->lawManager->add($law);
								$faction->credits -= LawResources::getInfo($type, 'price');
								$this->colorManager->sendSenateNotif($faction);
							} else {
								throw new ErrorException('Pas plus que 15.');
							}
						} else {
							if ($taxes <= 15 && $taxes >= 2) {
								$law->options = serialize(['taxes' => $taxes, 'rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => $commercialTaxRepository->get()->exportTax . ' %', 'Taxe proposée' => $taxes . ' %']]);
								$this->lawManager->add($law);
								$faction->credits -= LawResources::getInfo($type, 'price');
								$this->colorManager->sendSenateNotif($faction);
							} else {
								throw new ErrorException('Entre 2 et 15.');
							}
						}
					} else {
						throw new ErrorException('Cette faction n\'existe pas.');
					}
				} else {
					throw new ErrorException('Informations manquantes.');
				}
				break;
			case Law::COMTAXIMPORT:
				$taxes = intval($request->request->get('taxes'));
				$rColor = $request->request->get('rcolor');
				if (false !== $taxes && false !== $rColor) {
					$_CTM = $this->commercialTaxRepository->getCurrentsession();
					$this->commercialTaxRepository->load(['faction' => $currentPlayer->getRColor(), 'relatedFaction' => $rColor]);
					if ($this->commercialTaxRepository->size() > 0) {
						if ($this->commercialTaxRepository->get()->relatedFaction == $currentPlayer->getRColor()) {
							if ($taxes <= 15) {
								$law->options = serialize(['taxes' => $taxes, 'rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => $commercialTaxRepository->get()->importTax . ' %', 'Taxe proposée' => $taxes . ' %']]);
								$this->lawManager->add($law);
								$faction->credits -= LawResources::getInfo($type, 'price');
								$this->colorManager->sendSenateNotif($faction);
							} else {
								throw new ErrorException('Pas plus que 15.');
							}
						} else {
							if ($taxes <= 15 && $taxes >= 2) {
								$law->options = serialize(['taxes' => $taxes, 'rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => $commercialTaxRepository->get()->importTax . ' %', 'Taxe proposée' => $taxes . ' %']]);
								$this->lawManager->add($law);
								$faction->credits -= LawResources::getInfo($type, 'price');
								$this->colorManager->sendSenateNotif($faction);
							} else {
								throw new ErrorException('Entre 2 et 15.');
							}
						}
					} else {
						throw new ErrorException('Cette faction n\'existe pas.');
					}
				} else {
					throw new ErrorException('Informations manquantes.');
				}
				break;
			case Law::NEUTRALPACT:
				$rColor = $request->request->get('rcolor');
				if (false !== $rColor) {
					if ($rColor >= 1 && $rColor <= (ColorResource::size() - 1) && $rColor != $faction->id) {
						if (Color::NEUTRAL != $faction->colorLink[$rColor]) {
							$law->options = serialize(['rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName')]]);
							$this->lawManager->add($law);
							$faction->credits -= LawResources::getInfo($type, 'price');
							$this->colorManager->sendSenateNotif($faction, true);
						} else {
							throw new ErrorException('Vous considérez déjà cette faction comme votre alliée.');
						}
					} else {
						throw new ErrorException('Cette faction n\'existe pas ou il s\'agit de la votre.');
					}
				} else {
					throw new ErrorException('Informations manquantes.');
				}
				break;
			case Law::PEACEPACT:
				$rColor = $request->request->get('rcolor');
				if (false !== $rColor) {
					if ($rColor >= 1 && $rColor <= (ColorResource::size() - 1) && $rColor != $faction->id) {
						$nbrPact = 0;
						foreach ($faction->colorLink as $relation) {
							if (Color::PEACE == $relation) {
								++$nbrPact;
							}
						}
						if ($nbrPact < 2) {
							if (Color::PEACE != $faction->colorLink[$rColor]) {
								$law->options = serialize(['rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName')]]);
								$this->lawManager->add($law);
								$faction->credits -= LawResources::getInfo($type, 'price');
								$this->colorManager->sendSenateNotif($faction, true);
							} else {
								throw new ErrorException('Vous considérez déjà cette faction comme votre alliée.');
							}
						} else {
							throw new ErrorException('Vous ne pouvez faire que 2 pactes de ce type.');
						}
					} else {
						throw new ErrorException('Cette faction n\'existe pas ou il s\'agit de la votre.');
					}
				} else {
					throw new ErrorException('Informations manquantes.');
				}
				break;
			case Law::TOTALALLIANCE:
				$rColor = $request->request->get('rcolor');
				if (false !== $rColor) {
					if ($rColor >= 1 && $rColor <= (ColorResource::size() - 1) && $rColor != $faction->id) {
						$allyYet = false;
						foreach ($faction->colorLink as $relation) {
							if (Color::ALLY == $relation) {
								$allyYet = true;
							}
						}
						if (!$allyYet) {
							if (Color::ALLY != $faction->colorLink[$rColor]) {
								$law->options = serialize(['rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName')]]);
								$this->lawManager->add($law);
								$faction->credits -= LawResources::getInfo($type, 'price');
								$this->colorManager->sendSenateNotif($faction, true);
							} else {
								throw new ErrorException('Vous considérez déjà cette faction comme votre alliée.');
							}
						} else {
							throw new ErrorException('Vous ne pouvez considérez qu\'une seule faction comme alliée.');
						}
					} else {
						throw new ErrorException('Cette faction n\'existe pas ou il s\'agit de la votre.');
					}
				} else {
					throw new ErrorException('Informations manquantes.');
				}
				break;
			case Law::WARDECLARATION:
				$rColor = $request->request->get('rcolor');
				if (false !== $rColor) {
					if ($rColor >= 1 && $rColor <= (ColorResource::size() - 1) && $rColor != $faction->id) {
						if (Color::ENEMY != $faction->colorLink[$rColor]) {
							$law->options = serialize(['rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName')]]);
							$this->lawManager->add($law);
							$faction->credits -= LawResources::getInfo($type, 'price');
							$this->colorManager->sendSenateNotif($faction, true);
						} else {
							throw new ErrorException('Vous considérez déjà cette faction comme votre ennemmi.');
						}
					} else {
						throw new ErrorException('Cette faction n\'existe pas ou il s\'agit de la votre.');
					}
				} else {
					throw new ErrorException('Informations manquantes.');
				}
				break;
			case Law::PUNITION:
				$rPlayer = $request->request->get('rplayer') ??
					throw new BadRequestHttpException('Missing player id');
				$credits = $request->request->getInt('credits') ?? throw new BadRequestHttpException('Missing fine amount');

				if ($credits <= 0) {
					throw new BadRequestHttpException('l\'amende doit être un entier positif.');
				}
				$targetPlayer = $this->playerRepository->get($rPlayer);
				if ($targetPlayer->faction->id !== $currentPlayer->faction->id) {
					throw $this->createAccessDeniedException('Ce joueur n\'est pas de votre faction.');
				}
				$law->options = serialize(['rPlayer' => $rPlayer, 'credits' => $credits, 'display' => ['Joueur' => $targetPlayer->name, 'amende' => $credits]]);
				$faction->credits -= LawResources::getInfo($type, 'price');
				$this->colorManager->sendSenateNotif($faction);
				break;
			default:
				throw new BadRequestHttpException('Cette loi n\'existe pas.');
		}
	}
}
