<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\BuildingQueueManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\RecyclingMissionManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Gaia\Event\PlaceOwnerChangeEvent;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Resource\PlaceResource;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// @TODO Simplify this hell
class ChangeBaseType extends AbstractController
{
	public function __invoke(
		Request $request,
		OrbitalBase $currentBase,
		Player $currentPlayer,
		CommanderManager $commanderManager,
		BuildingQueueManager $buildingQueueManager,
		RecyclingMissionManager $recyclingMissionManager,
		OrbitalBaseManager $orbitalBaseManager,
		OrbitalBaseHelper $orbitalBaseHelper,
		PlaceManager $placeManager,
		PlayerManager $playerManager,
		EventDispatcherInterface $eventDispatcher,
		EntityManager $entityManager,
	): Response {
		$session = $request->getSession();

		$baseId = $currentBase->getId();
		$type = $request->query->get('type');

		if (false !== $type) {
			if (($orbitalBase = $orbitalBaseManager->getPlayerBase($baseId, $currentPlayer->getId())) !== null) {
				$player = $currentPlayer;

				if (OrbitalBase::TYP_NEUTRAL === $orbitalBase->typeOfBase) {
					if ($orbitalBase->levelGenerator >= $this->getParameter('athena.obm.change_type_min_level')) {
						switch ($type) {
							case OrbitalBase::TYP_COMMERCIAL:
								$totalPrice = PlaceResource::get(OrbitalBase::TYP_COMMERCIAL, 'price');
								if ($player->credit >= $totalPrice) {
									$orbitalBase->typeOfBase = $type;
									$playerManager->decreaseCredit($player, $totalPrice);

									$this->addFlash('success', $orbitalBase->name.' est désormais un Centre Industriel');
								} else {
									throw new ErrorException('Evolution de votre colonie impossible - vous n\'avez pas assez de crédits');
								}
								break;
							case OrbitalBase::TYP_MILITARY:
								$totalPrice = PlaceResource::get(OrbitalBase::TYP_MILITARY, 'price');
								if ($player->credit >= $totalPrice) {
									$orbitalBase->typeOfBase = $type;
									$playerManager->decreaseCredit($player, $totalPrice);

									$this->addFlash('success', $orbitalBase->name.' est désormais une Base Militaire');
								} else {
									throw new ErrorException('Evolution de votre colonie impossible - vous n\'avez pas assez de crédits');
								}
								break;
							default:
								throw new ErrorException('Modification du type de la base orbitale impossible (seulement commercial ou militaire)');
						}
					} else {
						throw new ErrorException('Evolution de votre colonie impossible - niveau du générateur pas assez élevé');
					}
				} elseif (OrbitalBase::TYP_COMMERCIAL == $orbitalBase->typeOfBase || OrbitalBase::TYP_MILITARY == $orbitalBase->typeOfBase) {
					$baseMinLevelForCapital = $this->getParameter('athena.obm.capital_min_level');
					if (OrbitalBase::TYP_CAPITAL === $type) {
						if ($orbitalBase->levelGenerator >= $baseMinLevelForCapital) {
							$playerBases = $orbitalBaseManager->getPlayerBases($session->get('playerId'));

							$capitalQuantity = 0;
							foreach ($playerBases as $playerBase) {
								if (OrbitalBase::TYP_CAPITAL == $playerBase->typeOfBase) {
									++$capitalQuantity;
								}
							}
							if (0 == $capitalQuantity) {
								$totalPrice = PlaceResource::get(OrbitalBase::TYP_CAPITAL, 'price');
								if ($player->credit >= $totalPrice) {
									$orbitalBase->typeOfBase = $type;
									$playerManager->decreaseCredit($player, $totalPrice);

									$this->addFlash('success', $orbitalBase->name.' est désormais une capitale.');
								} else {
									throw new ErrorException('Modification du type de la base orbitale impossible - vous n\'avez pas assez de crédits');
								}
							} else {
								throw new ErrorException('Vous ne pouvez pas avoir plus d\'une Capitale. Sauf si vous en conquérez à vos ennemis bien sûr.');
							}
						} else {
							throw new ErrorException('Pour transformer votre base en capitale, vous devez augmenter votre générateur jusqu\'au niveau '.$baseMinLevelForCapital.'.');
						}
					} elseif (($orbitalBase->isCommercialBase() && OrbitalBase::TYP_MILITARY === $type)
						or ($orbitalBase->isMilitaryBase() && OrbitalBase::TYP_COMMERCIAL === $type)) {
						// commercial --> military OR military --> commercial
						$totalPrice = PlaceResource::get($type, 'price');
						if ($player->credit >= $totalPrice) {
							$canChangeBaseType = true;
							if (OrbitalBase::TYP_COMMERCIAL === $type) {
								// delete all recycling missions and logs
								$recyclingMissionManager->removeBaseMissions($orbitalBase->rPlace);

								// verify if fleets are moving or not
								// transfer to the mess the extra commanders and change line if needed
								$firstLineCommanders = $commanderManager->getCommandersByLine($orbitalBase->rPlace, 1);
								$totalQtyLine1 = count($firstLineCommanders);
								$movingQtyLine1 = 0;
								foreach ($firstLineCommanders as $commander) {
									if (Commander::MOVING == $commander->statement) {
										++$movingQtyLine1;
									}
								}
								$secondLineCommanders = $commanderManager->getCommandersByLine($orbitalBase->rPlace, 2);
								$totalQtyLine2 = count($secondLineCommanders);
								$movingQtyLine2 = 0;
								foreach ($secondLineCommanders as $commander) {
									if (Commander::MOVING == $commander->statement) {
										++$movingQtyLine2;
									}
								}

								$totalQty = $totalQtyLine1 + $totalQtyLine2;
								$movingQty = $movingQtyLine1 + $movingQtyLine2;

								if ($totalQty >= 2) {
									switch ($movingQty) {
										case 2:
											$line1 = false;
											$line2 = false;
											foreach ($firstLineCommanders as $commander) {
												if (Commander::MOVING == $commander->statement) {
													if ($line1) {
														// move to line 2
														$commander->line = 2;
														$line2 = true;
														continue;
													}
													// stay on line 1
													$line1 = true;
													continue;
												}
												// move to the mess
												$commander->statement = Commander::RESERVE;
												$commanderManager->emptySquadrons($commander);
											}
											foreach ($secondLineCommanders as $commander) {
												if (Commander::MOVING == $commander->statement) {
													if ($line2) {
														// move to line 1
														$commander->line = 1;
														$line1 = true;
														continue;
													}
													// stay on line 2
													$line2 = true;
													continue;
												}
												// move to the mess
												$commander->statement = Commander::RESERVE;
												$commanderManager->emptySquadrons($commander);
											}
											break;
										case 1:
											if (1 == $movingQtyLine1) {
												if ($totalQtyLine1 >= 1 && $totalQtyLine2 >= 1) {
													// let stay one cmder on each line
													foreach ($firstLineCommanders as $commander) {
														if (Commander::MOVING != $commander->statement) {
															// move to the mess
															$commander->statement = Commander::RESERVE;
															$commanderManager->emptySquadrons($commander);
														}
													}
													$line2 = false;
													foreach ($secondLineCommanders as $commander) {
														if (!$line2) {
															$line2 = true;
														} else {
															// move to the mess
															$commander->statement = Commander::RESERVE;
															$commanderManager->emptySquadrons($commander);
														}
													}
												} else {
													// change line of one from line 1 to 2
													$line2 = false;
													foreach ($firstLineCommanders as $commander) {
														if (Commander::MOVING != $commander->statement) {
															if (!$line2) {
																$line2 = true;
															} else {
																// move to the mess
																$commander->statement = Commander::RESERVE;
																$commanderManager->emptySquadrons($commander);
															}
														}
													}
												}
											} else { // $movingQtyLine2 == 1
												if ($totalQtyLine1 >= 1 && $totalQtyLine2 >= 1) {
													// let stay one cmder on each line
													foreach ($secondLineCommanders as $commander) {
														if (Commander::MOVING != $commander->statement) {
															// move to the mess
															$commander->statement = Commander::RESERVE;
															$commanderManager->emptySquadrons($commander);
														}
													}
													$line1 = false;
													foreach ($firstLineCommanders as $commander) {
														if (!$line1) {
															$line1 = true;
														} else {
															// move to the mess
															$commander->statement = Commander::RESERVE;
															$commanderManager->emptySquadrons($commander);
														}
													}
												} else {
													// change line of one from line 2 to 1
													$line1 = false;
													foreach ($firstLineCommanders as $commander) {
														if (Commander::MOVING != $commander->statement) {
															if (!$line1) {
																$line1 = true;
															} else {
																// move to the mess
																$commander->statement = Commander::RESERVE;
																$commanderManager->emptySquadrons($commander);
															}
														}
													}
												}
											}
											break;
										case 0:
											if (0 == $totalQtyLine1) {
												// one from line 2 to line 1
												$line1 = false;
												$line2 = false;
												foreach ($firstLineCommanders as $commander) {
													if (!$line1) {
														$line1 = true;
													} elseif (!$line2) {
														// move one to line 2
														$commander->line = 2;
														$line2 = true;
													} else {
														// move to the mess
														$commander->statement = Commander::RESERVE;
														$commanderManager->emptySquadrons($commander);
													}
												}
											} elseif (0 == $totalQtyLine2) {
												// one from line 1 to line 2
												$line1 = false;
												$line2 = false;
												foreach ($secondLineCommanders as $commander) {
													if (!$line2) {
														$line2 = true;
													} elseif (!$line1) {
														// move one to line 1
														$commander->line = 1;
														$line1 = true;
													} else {
														// move to the mess
														$commander->statement = Commander::RESERVE;
														$commanderManager->emptySquadrons($commander);
													}
												}
											} else {
												// one on each line
												$line1 = false;
												foreach ($firstLineCommanders as $commander) {
													if (!$line1) {
														$line1 = true;
													} else {
														// move to the mess
														$commander->statement = Commander::RESERVE;
														$commanderManager->emptySquadrons($commander);
													}
												}
												$line2 = false;
												foreach ($secondLineCommanders as $commander) {
													if (!$line2) {
														$line2 = true;
													} else {
														// move to the mess
														$commander->statement = Commander::RESERVE;
														$commanderManager->emptySquadrons($commander);
													}
												}
											}
											break;
										default:
											// the user can't change base type to commercial right now !
											$canChangeBaseType = false;
									}
								} else {
									if (2 == $totalQtyLine1) {
										// switch one from line 1 to line 2
										$firstLineCommanders[0]->line = 2;
									}
									if (2 == $totalQtyLine2) {
										// switch one from line 2 to line 1
										$secondLineCommanders[1]->line = 1;
									}
								}
							}
							if ($canChangeBaseType) {
								$playerManager->decreaseCredit($player, $totalPrice);
								$orbitalBase->typeOfBase = $type;
								// delete commercial buildings
								for ($i = 0; $i < OrbitalBaseResource::BUILDING_QUANTITY; ++$i) {
									$maxLevel = $orbitalBaseHelper->getBuildingInfo($i, 'maxLevel', $type);
									if ($orbitalBase->getBuildingLevel($i) > $maxLevel) {
										$orbitalBase->setBuildingLevel($i, $maxLevel);
									}
								}
								// delete buildings in queue
								$buildingQueues = $buildingQueueManager->getBaseQueues($baseId);
								foreach ($buildingQueues as $buildingQueue) {
									$entityManager->remove($buildingQueue);
								}
								$entityManager->flush();
								// send the right alert
								if (OrbitalBase::TYP_COMMERCIAL == $type) {
									$this->addFlash('success', 'Votre Base Militaire devient un Centre Commerciale. Vos bâtiments militaires superflus sont détruits.');
								} else {
									$this->addFlash('success', 'Votre Centre Industriel devient une Base Militaire. Vos bâtiments commerciaux superflus sont détruits.');
								}
							} else {
								throw new ErrorException('modification du type de la base orbitale impossible - vous avez trop de flottes en mouvement pour changer votre base en Centre Industriel');
							}
						} else {
							throw new ErrorException('modification du type de la base orbitale impossible - vous n\'avez pas assez de crédits');
						}
					} else {
						throw new ErrorException('modification du type de la base orbitale impossible (seulement capitale, commercial ou militaire)');
					}
				} elseif (OrbitalBase::TYP_CAPITAL == $orbitalBase->typeOfBase) {
					/*switch ($type) {
						case OrbitalBase::TYP_COMMERCIAL:
							$orbitalBase->typeOfBase = $type;
							# casser les bâtiments en trop
							# killer la file de construction
							throw new ErrorException('Votre base orbitale devient commerciale.', ALERT_STD_SUCCESS);
							break;
						case OrbitalBase::TYP_MILITARY:
							$orbitalBase->typeOfBase = $type;
							# casser les bâtiments en trop
							# killer la file de construction
							throw new ErrorException('Votre base orbitale devient militaire.', ALERT_STD_SUCCESS);
							break;
						default :
							throw new ErrorException('modification du type de la base orbitale impossible (seulement commercial ou militaire)', ALERT_STD_ERROR);
							break;
					}*/
					throw new ErrorException('modification du type de la base orbitale impossible - c\'est déjà une capitale !');
				} else {
					throw new ErrorException('modification du type de la base orbitale impossible - type invalide');
				}
			} else {
				throw new ErrorException('cette base ne vous appartient pas');
			}
		} else {
			throw new FormException('pas assez d\'informations pour changer le type de la base orbitale');
		}
		$entityManager->flush();
		$entityManager->getRepository(OrbitalBase::class)->updateBuildingLevels($orbitalBase);
		$eventDispatcher->dispatch(new PlaceOwnerChangeEvent($placeManager->get($orbitalBase->getId())), PlaceOwnerChangeEvent::NAME);

		return $this->redirectToRoute('base_overview');
	}
}
