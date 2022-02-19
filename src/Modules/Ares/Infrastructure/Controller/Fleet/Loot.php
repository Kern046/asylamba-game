<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Game;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Manager\SectorManager;
use App\Modules\Gaia\Model\Place;
use App\Modules\Zeus\Helper\TutorialHelper;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Loot extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ColorManager $colorManager,
		CommanderManager $commanderManager,
		PlaceManager $placeManager,
		PlayerManager $playerManager,
		SectorManager $sectorManager,
		TutorialHelper $tutorialHelper,
		EntityManager $entityManager,
		int $id,
		int $placeId,
	): Response {
		$session = $request->getSession();
		$place = $placeManager->get($placeId);
		if (null === $place->rPlayer || ($player = $playerManager->get($place->rPlayer)) === null) {
			if (($commander = $commanderManager->get($id)) !== null && $commander->rPlayer === $currentPlayer->getId()) {
				if ($place !== null) {
					if ($place->typeOfPlace == Place::TERRESTRIAL) {
						if ($currentPlayer->getRColor() != $place->getPlayerColor()) {
							$home = $placeManager->get($commander->getRBase());

							$length = Game::getDistance($home->getXSystem(), $place->getXSystem(), $home->getYSystem(), $place->getYSystem());
							$duration = Game::getTimeToTravel($home, $place, $session->get('playerBonus'));

							if ($commander->getPev() > 0) {
								if ($commander->statement == Commander::AFFECTED) {

									$sector = $sectorManager->get($place->rSector);

									$sectorColor = $colorManager->get($sector->rColor);
									$isFactionSector = $sector->rColor == $commander->playerColor || $sectorColor->colorLink[$currentPlayer->getRColor()] == Color::ALLY;

									$commander->destinationPlaceName = $place->baseName;
									if ($length <= Commander::DISTANCEMAX || $isFactionSector) {
										$commanderManager->move($commander, $place->getId(), $commander->rBase, Commander::LOOT, $length, $duration) ;
										$this->addFlash('success', 'Flotte envoyée.');
										# tutorial
										if ($session->get('playerInfo')->get('stepDone') == FALSE &&
											$session->get('playerInfo')->get('stepTutorial') === TutorialResource::LOOT_PLANET) {
											$tutorialHelper->setStepDone();
										}

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
							throw new ErrorException('Vous ne pouvez pas attaquer un lieu appartenant à votre Faction.');
						}
					} else {
						throw new ErrorException('Ce lieu n\'est pas habité.');
					}
				} else {
					throw new ErrorException('Ce lieu n\'existe pas.');
				}
			} else {
				throw new ErrorException('Ce commandant ne vous appartient pas ou n\'existe pas.');
			}
		} elseif ($player->level > 1 || $player->statement >= \App\Modules\Zeus\Model\Player::DELETED) {
			if (($commander = $commanderManager->get($id)) !== null && $commander->rPlayer === $currentPlayer->getId()) {
				if ($place !== null) {
					$color = $colorManager->get($currentPlayer->getRColor());

					if ($currentPlayer->getRColor() != $place->getPlayerColor() && $color->colorLink[$player->rColor] != Color::ALLY) {
						$home = $placeManager->get($commander->getRBase());

						$length = Game::getDistance($home->getXSystem(), $place->getXSystem(), $home->getYSystem(), $place->getYSystem());
						$duration = Game::getTimeToTravel($home, $place, $session->get('playerBonus'));

						if ($commander->getPev() > 0) {
							$sector = $sectorManager->get($place->rSector);
							$sectorColor = $colorManager->get($sector->rColor);

							$isFactionSector = $sector->rColor == $commander->playerColor || $sectorColor->colorLink[$currentPlayer->getRColor()] == Color::ALLY;

							$commander->destinationPlaceName = $place->baseName;
							if ($length <= Commander::DISTANCEMAX || $isFactionSector) {
								$commanderManager->move($commander, $place->getId(), $commander->rBase, Commander::LOOT, $length, $duration) ;
								$this->addFlash('success', 'Flotte envoyée.');

								$entityManager->flush();

								if ($request->query->has('redirect')) {
									return $this->redirectToRoute('map', ['place' => $request->query->get('redirect')]);
								}
								return $this->redirect($request->headers->get('referer'));
							} else {
								throw new ErrorException('Ce lieu est trop éloigné.');
							}
						} else {
							throw new ErrorException('Vous devez affecter au moins un vaisseau à votre officier.');
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
			throw new ErrorException('Vous ne pouvez pas piller un joueur de niveau 1.');
		}
	}
}
