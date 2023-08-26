<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Classes\Library\Parser;
use App\Modules\Athena\Domain\Repository\CommercialTaxRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class CreateLaw extends AbstractController
{
	public function __construct(
		private ColorManager                     $colorManager,
		private ColorRepositoryInterface $colorRepository,
		private CommercialTaxRepositoryInterface $commercialTaxRepository,
		private LawManager                       $lawManager,
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

		[$statement, $voteEndedAt, $endedAt, $options] = $this->createLaw($type, $duration, $faction, $currentPlayer, $request);

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
	public function createLaw(
		float|bool|int|string $type,
		int|null $duration,
		Color|null $faction,
		Player $currentPlayer,
		Request $request
	): array {
		$isRulerLaw = Player::CHIEF === LawResources::getInfo($type, 'department');
		$statement = $isRulerLaw ? Law::EFFECTIVE : Law::VOTATION;

		$voteEndedAt = $isRulerLaw
			? new \DateTimeImmutable()
			: new \DateTimeImmutable('+' . Law::VOTEDURATION . ' seconds');

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
			$options = [];

			if ($this->lawRepository->lawExists($currentPlayer->faction, $type)) {
				throw new ConflictHttpException('Cette loi est déjà proposée ou en vigueur.');
			}

			$faction->credits -= $lawPrice;
			$this->colorManager->sendSenateNotif($faction, $isRulerLaw);

			return [
				'statement' => $statement,
				'voteEndedAt' => $voteEndedAt,
				'endedAt' => $endedAt,
				'options' => $options,
			];
		}
		if (!$faction->canAfford(LawResources::getInfo($type, 'price'))) {
			throw new ConflictHttpException('Il n\'y assez pas a de crédits dans les caisses de l\'Etat.');
		}

		$options = match ($type) {
			Law::SECTORTAX => $this->createSectorTaxLaw($request, $currentPlayer),
			Law::SECTORNAME => $this->createSectorNameLaw($request, $currentPlayer),
			Law::COMTAXEXPORT => $this->createCommercialExportTaxLaw($request, $currentPlayer),
			Law::COMTAXIMPORT => $this->createCommercialImportTaxLaw($request, $currentPlayer),
			Law::NEUTRALPACT => $this->createNeutralPactLaw($request, $faction),
			Law::PEACEPACT => $this->createPeacePactLaw($request, $faction),
			Law::TOTALALLIANCE => $this->createTotalAllianceLaw($request, $faction),
			Law::WARDECLARATION => $this->createWarDeclarationLaw($request, $faction),
			Law::PUNITION => $this->createPunitionLaw($request, $currentPlayer),
			default => throw new BadRequestHttpException('Cette loi n\'existe pas.'),
		};
		$faction->credits -= LawResources::getInfo($type, 'price');

		$this->colorManager->sendSenateNotif($faction, $isRulerLaw);

		return [
			'statement' => $statement,
			'voteEndedAt' => $voteEndedAt,
			'endedAt' => $endedAt,
			'options' => $options,
		];
	}

	/**
	 * @param Request $request
	 * @param Player $currentPlayer
	 * @param float|bool|int|string $type
	 * @param Color|null $faction
	 * @return void
	 */
	public function createSectorTaxLaw(Request $request, Player $currentPlayer): array
	{
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
		return [
			'taxes' => $taxes,
			'rSector' => $rSector,
			'display' => [
				'Secteur' => $sector->name,
				'Taxe actuelle' => $sector->tax . ' %',
				'Taxe proposée' => $taxes . ' %'
			],
		];
	}

	/**
	 * @return array
	 */
	public function createSectorNameLaw(Request $request, Player $currentPlayer): array
	{
		$rSector = $request->request->get('rsector') ?? throw new BadRequestHttpException('Missing sector');
		$name = $request->request->get('name') ?? throw new BadRequestHttpException('Missing name');
		if (strlen($name) < 1 and strlen($name) > 50) {
			throw new ConflictHttpException('Le nom doit faire entre 1 et 50 caractères.');
		}
		$name = $this->parser->protect($name);
		$sector = $this->sectorRepository->get(Uuid::fromString($rSector))
			?? throw $this->createNotFoundException('Ce secteur n\'existe pas.');
		if ($sector->faction->id !== $currentPlayer->faction->id) {
			throw new ConflictHttpException('Ce secteur n\'est pas sous votre contrôle.');
		}
		return [
			'name' => $name,
			'rSector' => $rSector,
		];
	}

	public function createCommercialExportTaxLaw(Request $request, Player $currentPlayer): array
	{
		$taxes = $request->request->get('taxes') ?? throw new BadRequestHttpException('Missing taxes');
		$rColor = $request->request->get('rcolor') ?? throw new BadRequestHttpException('Missing related faction');

		$relatedFaction = $this->colorRepository->get(Uuid::fromString($rColor)) ?? throw new NotFoundHttpException('Faction not found');

		$commercialTaxes = $this->commercialTaxRepository->getFactionsTax($currentPlayer->faction, $relatedFaction);

		if ($relatedFaction->id === $currentPlayer->faction->id && $taxes > 15) {
			throw new BadRequestHttpException('Pas plus que 15.');
		} elseif ($taxes > 15 || $taxes < 2) {
			throw new BadRequestHttpException('Entre 2 et 15.');
		}
		return [
			'taxes' => $taxes,
			'rColor' => $rColor,
			'display' => [
				'Faction' => ColorResource::getInfo($rColor, 'officialName'),
				'Taxe actuelle' => $commercialTaxes->exportTax . ' %',
				'Taxe proposée' => $taxes . ' %',
			],
		];
	}

	public function createCommercialImportTaxLaw(Request $request, Player $currentPlayer): array
	{
		$taxes = $request->request->get('taxes') ?? throw new BadRequestHttpException('Missing taxes');
		$rColor = $request->request->get('rcolor') ?? throw new BadRequestHttpException('Missing related faction');

		$relatedFaction = $this->colorRepository->get(Uuid::fromString($rColor)) ?? throw new NotFoundHttpException('Faction not found');

		$commercialTaxes = $this->commercialTaxRepository->getFactionsTax($currentPlayer->faction, $relatedFaction);

		if ($relatedFaction->id === $currentPlayer->faction->id && $taxes > 15) {
			throw new BadRequestHttpException('Pas plus que 15.');
		} elseif ($taxes > 15 || $taxes < 2) {
			throw new BadRequestHttpException('Entre 2 et 15.');
		}
		return [
			'taxes' => $taxes,
			'rColor' => $rColor,
			'display' => [
				'Faction' => ColorResource::getInfo($rColor, 'officialName'),
				'Taxe actuelle' => $commercialTaxes->importTax . ' %',
				'Taxe proposée' => $taxes . ' %',
			],
		];
	}

	/**
	 * @return array
	 */
	public function createNeutralPactLaw(Request $request, Color|null $faction): array
	{
		$rColor = $request->request->get('rcolor')
			?? throw new BadRequestHttpException('Informations manquantes.');

		if ($rColor < 1 || $rColor > (ColorResource::size() - 1) || $rColor === $faction->identifier) {
			throw new ConflictHttpException('Cette faction n\'existe pas ou il s\'agit de la votre.');
		}

		if (Color::NEUTRAL === $faction->relations[$rColor]) {
			throw new ConflictHttpException('Vous considérez déjà cette faction comme neutre.');
		}

		return [
			'rColor' => $rColor,
			'display' => [
				'Faction' => ColorResource::getInfo($rColor, 'officialName'),
			],
		];
	}

	public function createPeacePactLaw(Request $request, Color|null $faction): array
	{
		$rColor = $request->request->get('rcolor')
			?? throw new BadRequestHttpException('Informations manquantes.');
		if ($rColor < 1 || $rColor > (ColorResource::size() - 1) || $rColor === $faction->identifier) {
			throw new ConflictHttpException('Cette faction n\'existe pas ou il s\'agit de la votre.');
		}
		$nbrPact = 0;
		foreach ($faction->relations as $relation) {
			if (Color::PEACE == $relation) {
				++$nbrPact;
			}
		}
		if ($nbrPact >= 2) {
			throw new ConflictHttpException('Vous ne pouvez faire que 2 pactes de ce type.');
		}

		if (Color::PEACE === $faction->relations[$rColor]) {
			throw new ConflictHttpException('Vous considérez déjà cette faction comme votre alliée.');
		}

		return [
			'rColor' => $rColor,
			'display' => [
				'Faction' => ColorResource::getInfo($rColor, 'officialName'),
			],
		];
	}

	/**
	 * @return array
	 */
	public function createTotalAllianceLaw(Request $request, Color|null $faction): array
	{
		$rColor = $request->request->get('rcolor')
			?? throw new BadRequestHttpException('Informations manquantes.');
		if ($rColor < 1 || $rColor > (ColorResource::size() - 1) || $rColor === $faction->identifier) {
			throw new ConflictHttpException('Cette faction n\'existe pas ou il s\'agit de la votre.');
		}
		$allyYet = false;
		foreach ($faction->relations as $relation) {
			if (Color::ALLY === $relation) {
				$allyYet = true;
			}
		}
		if ($allyYet) {
			throw new ConflictHttpException('Vous ne pouvez considérez qu\'une seule faction comme alliée.');
		}

		if (Color::ALLY === $faction->relations[$rColor]) {
			throw new ConflictHttpException('Vous considérez déjà cette faction comme votre alliée.');
		}

		return [
			'rColor' => $rColor,
			'display' => [
				'Faction' => ColorResource::getInfo($rColor, 'officialName'),
			],
		];
	}

	/**
	 * @return void
	 */
	public function createWarDeclarationLaw(Request $request, Color|null $faction): array
	{
		$rColor = $request->request->get('rcolor')
			?? throw new BadRequestHttpException('Informations manquantes.');

		if ($rColor < 1 || $rColor > (ColorResource::size() - 1) || $rColor === $faction->identifier) {
			throw new ConflictHttpException('Cette faction n\'existe pas ou il s\'agit de la votre.');
		}
		if (Color::ENEMY === $faction->relations[$rColor]) {
			throw new ConflictHttpException('Vous considérez déjà cette faction comme votre ennemmi.');
		}

		return [
			'rColor' => $rColor,
			'display' => [
				'Faction' => ColorResource::getInfo($rColor, 'officialName')
			],
		];
	}

	/**
	 * @return array
	 */
	public function createPunitionLaw(Request $request, Player $currentPlayer): array
	{
		$rPlayer = $request->request->get('rplayer')
			?? throw new BadRequestHttpException('Missing player id');
		$credits = $request->request->getInt('credits')
			?? throw new BadRequestHttpException('Missing fine amount');

		if ($credits <= 0) {
			throw new BadRequestHttpException('l\'amende doit être un entier positif.');
		}
		$targetPlayer = $this->playerRepository->get($rPlayer);
		if ($targetPlayer->faction->id !== $currentPlayer->faction->id) {
			throw $this->createAccessDeniedException('Ce joueur n\'est pas de votre faction.');
		}

		return [
			'rPlayer' => $rPlayer,
			'credits' => $credits,
			'display' => [
				'Joueur' => $targetPlayer->name,
				'amende' => $credits,
			],
		];
	}
}
