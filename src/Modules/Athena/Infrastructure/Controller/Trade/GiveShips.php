<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\CommercialShippingManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\TransactionManager;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GiveShips extends AbstractController
{
	public function __invoke(
		Request $request,
		CommercialShippingManager $commercialShippingManager,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		OrbitalBaseManager $orbitalBaseManager,
		OrbitalBaseHelper $orbitalBaseHelper,
		PlaceManager $placeManager,
		TransactionManager $transactionManager,
		NotificationManager $notificationManager,
		EntityManager $entityManager,
	): Response {
		$baseId = $request->request->get('baseId');

		if ($baseId != $currentBase->getId()) {
			for ($i = 0; $i < ShipResource::SHIP_QUANTITY; $i++) {
				if ($request->request->has('identifier-' . $i)) {
					$shipType = $i;
					$shipName = ShipResource::getInfo($i, 'codeName');

					if ($request->request->has('quantity-' . $i)) {
						$ships = $request->request->get('quantity-' . $i) > 0
							? $request->request->get('quantity-' . $i) : 1;
						$ships = intval($ships);
					}

					break;
				}
			}

			if (isset($shipType) AND isset($ships)) {
				if (ShipResource::isAShipFromDock1($shipType) OR ShipResource::isAShipFromDock2($shipType)) {
					if ($ships > 0) {
						if ($currentBase->getShipStorage($shipType) >= $ships) {
							$commercialShipQuantity = Game::getCommercialShipQuantityNeeded(Transaction::TYP_SHIP, $ships, $shipType);
							$totalShips = $orbitalBaseHelper->getBuildingInfo(6, 'level', $currentBase->getLevelCommercialPlateforme(), 'nbCommercialShip');
							$usedShips = 0;

							foreach ($currentBase->commercialShippings as $commercialShipping) {
								if ($commercialShipping->rBase == $currentBase->rPlace) {
									$usedShips += $commercialShipping->shipQuantity;
								}
							}

							$remainingShips = $totalShips - $usedShips;

							if ($remainingShips >= $commercialShipQuantity) {
								if (($otherBase = $orbitalBaseManager->get($baseId)) !== null) {
									# load places to compute travel time
									$startPlace = $placeManager->get($currentBase->getRPlace());
									$destinationPlace = $placeManager->get($otherBase->getRPlace());
									$timeToTravel = Game::getTimeToTravelCommercial($startPlace, $destinationPlace);
									$departure = Utils::now();
									$arrival = Utils::addSecondsToDate($departure, $timeToTravel);

									# création de la transaction
									$tr = new Transaction();
									$tr->rPlayer = $currentPlayer->getId();
									$tr->rPlace = $currentBase->getRPlace();
									$tr->type = Transaction::TYP_SHIP;
									$tr->quantity = $ships;
									$tr->identifier = $shipType;
									$tr->price = 0;
									$tr->commercialShipQuantity = $commercialShipQuantity;
									$tr->statement = Transaction::ST_COMPLETED;
									$tr->dPublication = Utils::now();
									$transactionManager->add($tr);

									# création du convoi
									$cs = new CommercialShipping();
									$cs->rPlayer = $currentPlayer->getId();
									$cs->rBase = $currentBase->getRPlace();
									$cs->rBaseDestination = $otherBase->getRPlace();
									$cs->rTransaction = $tr->id;
									$cs->resourceTransported = 0;
									$cs->shipQuantity = $commercialShipQuantity;
									$cs->dDeparture = $departure;
									$cs->dArrival = $arrival;
									$cs->statement = CommercialShipping::ST_GOING;
									$commercialShippingManager->add($cs);

									$currentBase->setShipStorage($shipType, $currentBase->getShipStorage($shipType) - $ships);

									if ($currentBase->getRPlayer() != $otherBase->getRPlayer()) {
										$n = new Notification();
										$n->setRPlayer($otherBase->getRPlayer());
										$n->setTitle('Envoi de vaisseaux');
										$n->addBeg()->addTxt($otherBase->getName())->addSep();
										$n->addLnk('embassy/player-' . $currentPlayer->getId(), $currentPlayer->getName());
										$n->addTxt(' a lancé un convoi de ')->addStg(Format::numberFormat($ships))->addTxt(' ' . $shipName . ' depuis sa base ');
										$n->addLnk('map/place-' . $currentBase->getRPlace(), $currentBase->getName())->addTxt('. ');
										$n->addBrk()->addTxt('Quand le convoi arrivera, les vaisseaux seront placés dans votre hangar.');
										$n->addSep()->addLnk('bases/base-' . $otherBase->getId()  . '/view-commercialplateforme/mode-market', 'vers la place du commerce →');
										$n->addEnd();

										$notificationManager->add($n);
									}

//											if (true === $this->getContainer()->getParameter('data_analysis')) {
//												$qr = $this->getContainer()->get(\App\Classes\Database\Database::class)->prepare('INSERT INTO
//											DA_CommercialRelation(`from`, `to`, type, weight, dAction)
//											VALUES(?, ?, ?, ?, ?)'
//												);
//												$qr->execute([$session->get('playerId'), $otherBase->getRPlayer(), 3, DataAnalysis::resourceToStdUnit(ShipResource::getInfo($shipType, 'resourcePrice') * $ships), Utils::now()]);
//											}
									$entityManager->flush();

									$this->addFlash('success', 'Vaisseaux envoyés');

									return $this->redirect($request->headers->get('referer'));
								} else {
									throw new ErrorException('Erreur dans les bases orbitales');
								}
							} else {
								throw new ErrorException('Vous n\'avez pas assez de vaisseaux de transport');
							}
						} else {
							throw new ErrorException('Vous n\'avez pas assez de vaisseaux de ce type');
						}
					} else {
						throw new ErrorException('Envoi de vaisseaux impossible');
					}
				} else {
					throw new ErrorException('Vaisseau inconnu');
				}
			} else {
				throw new ErrorException('erreur système');
			}
		} else {
			throw new ErrorException('envoi de vaisseau impossible');
		}
	}
}
