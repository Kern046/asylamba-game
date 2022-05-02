<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Game;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Manager\SectorManager;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Promethee\Model\TechnologyId;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Conquer extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $orbitalBase,
		CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		ColorManager $colorManager,
		CommanderManager $commanderManager,
		PlaceManager $placeManager,
		PlayerManager $playerManager,
		TechnologyManager $technologyManager,
		SectorManager $sectorManager,
		EntityManager $entityManager,
		int $id,
	): Response {
		$conquestCost = $this->getParameter('ares.coeff.conquest_cost');

		$place = $placeManager->get($request->query->getInt('placeId'));

		// load the technologies
		$technologies = $technologyManager->getPlayerTechnology($currentPlayer->getId());

		// check si technologie CONQUEST débloquée
		if (1 !== $technologies->getTechnology(TechnologyId::CONQUEST)) {
			throw new ConflictHttpException('Vous devez débloquer la technologie de conquête.');
		}
		// check si la technologie BASE_QUANTITY a un niveau assez élevé
		$maxBasesQuantity = $technologies->getTechnology(TechnologyId::BASE_QUANTITY) + 1;
		// @TODO Replace this count loop by a repository method
		$coloQuantity = 0;
		$commanders = $commanderManager->getPlayerCommanders($currentPlayer->getId(), [Commander::MOVING]);
		foreach ($commanders as $commander) {
			if (Commander::COLO == $commander->travelType) {
				++$coloQuantity;
			}
		}
		$totalBases = $currentPlayerBasesRegistry->count() + $coloQuantity;
		if ($totalBases >= $maxBasesQuantity) {
			throw new ConflictHttpException('Vous avez assez de conquête en cours ou un niveau d\'administration étendue trop faible.');
		}

		$targetPlayer = $playerManager->get($place->rPlayer);

		if ($targetPlayer->level > 3 || $targetPlayer->statement >= Player::DELETED) {
			if (($commander = $commanderManager->get($id)) !== null && $commander->rPlayer = $currentPlayer->getId()) {
				if (null !== $place) {
					$color = $colorManager->get($currentPlayer->getRColor());

					if ($currentPlayer->getRColor() != $place->getPlayerColor() && Color::ALLY != $color->colorLink[$targetPlayer->rColor]) {
						$home = $placeManager->get($commander->getRBase());

						$length = Game::getDistance($home->getXSystem(), $place->getXSystem(), $home->getYSystem(), $place->getYSystem());
						$duration = Game::getTimeToTravel($home, $place, $currentPlayerBonusRegistry->getPlayerBonus());

						// compute price
						$price = $totalBases * $conquestCost;

						// calcul du bonus
						if (in_array(ColorResource::COLOPRICEBONUS, $colorManager->get($currentPlayer->getRColor())->bonus)) {
							$price -= round($price * ColorResource::BONUS_CARDAN_COLO / 100);
						}

						if ($currentPlayer->getCredit() >= $price) {
							if ($commander->getPev() > 0) {
								if (Commander::AFFECTED == $commander->statement) {
									$sector = $sectorManager->get($place->rSector);

									$sectorColor = $colorManager->get($sector->rColor);
									$isFactionSector = $sector->rColor == $commander->playerColor || Color::ALLY == $sectorColor->colorLink[$currentPlayer->getRColor()];

									if ($length <= Commander::DISTANCEMAX || $isFactionSector) {
										$commander->destinationPlaceName = $place->baseName;

										$commanderManager->move($commander, $place->getId(), $commander->rBase, Commander::COLO, $length, $duration);
										// debit credit
										$playerManager->decreaseCredit($currentPlayer, $price);

										// throw new ErrorException('Flotte envoyée.', ALERT_STD_SUCCESS);

										$entityManager->flush();

										if ($request->query->has('redirect')) {
											return $this->redirectToRoute('map', ['place' => $request->query->get('redirect')]);
										}

										return $this->redirect($request->headers->get('referer'));
									} else {
										throw new ErrorException('Cet emplacement est trop éloigné.');
									}
								} else {
									throw new ErrorException('Cet officier est déjà en déplacement.');
								}
							} else {
								throw new ErrorException('Vous devez affecter au moins un vaisseau à votre officier.');
							}
						} else {
							throw new ErrorException('Vous n\'avez pas assez de crédits pour conquérir cette base.');
						}
					} else {
						throw new ErrorException('Vous ne pouvez pas attaquer un lieu appartenant à votre Faction ou d\'une faction alliée.');
					}
				} else {
					throw new ErrorException('Ce lieu n\'existe pas.');
				}
			} else {
				throw new ErrorException('Ce commandant ne vous appartient pas ou n\'existe pas.');
			}
		} else {
			throw new ErrorException('Vous ne pouvez pas conquérir un joueur de niveau 3 ou moins.');
		}
	}
}
