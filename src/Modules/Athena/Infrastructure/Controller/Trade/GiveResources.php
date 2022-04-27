<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade;

use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\CommercialShippingManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GiveResources extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        OrbitalBase $currentBase,
        OrbitalBaseManager $orbitalBaseManager,
        OrbitalBaseHelper $orbitalBaseHelper,
        PlaceManager $placeManager,
        CommercialShippingManager $commercialShippingManager,
        NotificationManager $notificationManager,
    ): Response {
        $baseId = $request->request->get('baseId');
        $quantity = $request->request->get('quantity');

        if (false !== $baseId and false !== $quantity) {
            if ($baseId != $currentBase->getId()) {
                $resource = intval($quantity);
                if ($resource > 0) {
                    if ($currentBase->getResourcesStorage() >= $resource) {
                        // ---------------------------
                        // controler le nombre de vaisseaux
                        // verif : have we enough commercialShips
                        $totalShips = $orbitalBaseHelper->getBuildingInfo(6, 'level', $currentBase->getLevelCommercialPlateforme(), 'nbCommercialShip');
                        $usedShips = 0;

                        foreach ($currentBase->commercialShippings as $commercialShipping) {
                            if ($commercialShipping->rBase == $currentBase->rPlace) {
                                $usedShips += $commercialShipping->shipQuantity;
                            }
                        }

                        $remainingShips = $totalShips - $usedShips;
                        $commercialShipQuantity = Game::getCommercialShipQuantityNeeded(Transaction::TYP_RESOURCE, $resource);

                        if ($remainingShips >= $commercialShipQuantity) {
                            if (($otherBase = $orbitalBaseManager->get($baseId)) !== null) {
                                // load places to compute travel time
                                $startPlace = $placeManager->get($currentBase->getRPlace());
                                $destinationPlace = $placeManager->get($otherBase->getRPlace());
                                $timeToTravel = Game::getTimeToTravelCommercial($startPlace, $destinationPlace);
                                $departure = Utils::now();
                                $arrival = Utils::addSecondsToDate($departure, $timeToTravel);

                                // création du convoi
                                $cs = new CommercialShipping();
                                $cs->rPlayer = $currentPlayer->getId();
                                $cs->rBase = $currentBase->rPlace;
                                $cs->rBaseDestination = $otherBase->rPlace;
                                $cs->resourceTransported = $resource;
                                $cs->shipQuantity = $commercialShipQuantity;
                                $cs->dDeparture = $departure;
                                $cs->dArrival = $arrival;
                                $cs->statement = CommercialShipping::ST_GOING;
                                $commercialShippingManager->add($cs);

                                $orbitalBaseManager->decreaseResources($currentBase, $resource);

                                if ($currentBase->getRPlayer() != $otherBase->getRPlayer()) {
                                    $n = new Notification();
                                    $n->setRPlayer($otherBase->getRPlayer());
                                    $n->setTitle('Envoi de ressources');
                                    $n->addBeg()->addTxt($otherBase->getName())->addSep();
                                    $n->addLnk('embassy/player-'.$currentPlayer->getId(), $currentPlayer->getName());
                                    $n->addTxt(' a lancé un convoi de ')->addStg(Format::numberFormat($resource))->addTxt(' ressources depuis sa base ');
                                    $n->addLnk('map/place-'.$currentBase->getRPlace(), $currentBase->getName())->addTxt('. ');
                                    $n->addBrk()->addTxt('Quand le convoi arrivera, les ressources seront à vous.');
                                    $n->addSep()->addLnk('bases/base-'.$otherBase->getId().'/view-commercialplateforme/mode-market', 'vers la place du commerce →');
                                    $n->addEnd();
                                    $notificationManager->add($n);
                                }

                                //									if (true === $this->getContainer()->getParameter('data_analysis')) {
                                //										$qr = $database->prepare('INSERT INTO
                                //									DA_CommercialRelation(`from`, `to`, type, weight, dAction)
                                //									VALUES(?, ?, ?, ?, ?)'
                                //										);
                                //										$qr->execute([$placeManager->get('playerId'), $otherBase->getRPlayer(), 4, DataAnalysis::resourceToStdUnit($resource), Utils::now()]);
                                //									}

                                $this->addFlash('success', 'Ressources envoyées');

                                return $this->redirect($request->headers->get('referer'));
                            } else {
                                throw new ErrorException('envoi de ressources impossible - erreur dans les bases orbitales');
                            }
                        } else {
                            throw new ErrorException('envoi de ressources impossible - vous n\'avez pas assez de vaisseaux de transport');
                        }
                    } else {
                        throw new ErrorException('envoi de ressources impossible - vous ne pouvez pas envoyer plus que ce que vous possédez');
                    }
                } else {
                    throw new ErrorException('envoi de ressources impossible - il faut envoyer un nombre entier positif');
                }
            } else {
                throw new ErrorException('envoi de ressources impossible - action inutile, vous ressources sont déjà sur cette base orbitale');
            }
        } else {
            throw new FormException('pas assez d\'informations pour envoyer des ressources');
        }
    }
}
