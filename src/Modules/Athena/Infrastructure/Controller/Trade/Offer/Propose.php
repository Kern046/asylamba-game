<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Offer;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\CommercialShippingManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\TransactionManager;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Propose extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        OrbitalBase $currentBase,
        OrbitalBaseManager $orbitalBaseManager,
        OrbitalBaseHelper $orbitalBaseHelper,
        CommanderManager $commanderManager,
        TransactionManager $transactionManager,
        CommercialShippingManager $commercialShippingManager,
        EntityManager $entityManager,
    ): Response {
        $type = $request->query->get('type');
        $quantity = $request->request->get('quantity');
        $identifier = $request->query->get('identifier');
        $price = $request->request->get('price');

        if (false !== $type and false !== $price) {
            $valid = true;

            switch ($type) {
                case Transaction::TYP_RESOURCE:
                    if (false !== $quantity and intval($quantity) > 0) {
                        $identifier = 0;
                    } else {
                        $valid = false;
                    }
                    break;
                case Transaction::TYP_SHIP:
                    if (false !== $identifier and ShipResource::isAShip($identifier)) {
                        if (ShipResource::isAShipFromDock1($identifier) or ShipResource::isAShipFromDock2($identifier)) {
                            if (false === $quantity) {
                                $quantity = 1;
                            } else {
                                if (intval($quantity) < 1) {
                                    $valid = false;
                                }
                            }
                        } else {
                            $valid = false;
                        }
                    } else {
                        $valid = false;
                    }
                    break;
                case Transaction::TYP_COMMANDER:
                    if (false === $identifier or $identifier < 1) {
                        $valid = false;
                    }
                    break;
                default:
                    $valid = false;
            }
            if ($valid) {
                $minPrice = Game::getMinPriceRelativeToRate($type, $quantity, $identifier);
                $maxPrice = Game::getMaxPriceRelativeToRate($type, $quantity, $identifier);

                if ($price < $minPrice) {
                    throw new BadRequestHttpException('Le prix que vous avez fixé est trop bas. Une limite inférieure est fixée selon la catégorie de la vente.');
                } elseif ($price > $maxPrice) {
                    throw new BadRequestHttpException('Le prix que vous avez fixé est trop haut. Une limite supérieure est fixée selon la catégorie de la vente.');
                } else {
                    $valid = true;

                    if ($valid) {
                        // verif : have we enough commercialShips
                        $totalShips = $orbitalBaseHelper->getBuildingInfo(OrbitalBaseResource::COMMERCIAL_PLATEFORME, 'level', $currentBase->getLevelCommercialPlateforme(), 'nbCommercialShip');
                        $usedShips = 0;

                        foreach ($currentBase->commercialShippings as $commercialShipping) {
                            if ($commercialShipping->rBase == $currentBase->getId()) {
                                $usedShips += $commercialShipping->shipQuantity;
                            }
                        }

                        // determine commercialShipQuantity needed
                        switch ($type) {
                            case Transaction::TYP_RESOURCE:
                                if ($currentBase->getResourcesStorage() >= $quantity) {
                                    $commercialShipQuantity = Game::getCommercialShipQuantityNeeded($type, $quantity);
                                } else {
                                    $valid = false;
                                }
                                break;
                            case Transaction::TYP_SHIP:
                                $inStorage = $currentBase->getShipStorage($identifier);
                                if ($inStorage >= $quantity) {
                                    $commercialShipQuantity = Game::getCommercialShipQuantityNeeded($type, $quantity, $identifier);
                                } else {
                                    $valid = false;
                                }
                                break;
                            case Transaction::TYP_COMMANDER:
                                $commercialShipQuantity = Game::getCommercialShipQuantityNeeded($type, $quantity);
                                break;
                        }

                        $remainingShips = $totalShips - $usedShips;
                        if ($valid) {
                            if ($remainingShips >= $commercialShipQuantity) {
                                switch ($type) {
                                    case Transaction::TYP_RESOURCE:
                                        $orbitalBaseManager->decreaseResources($currentBase, $quantity);
                                        break;
                                    case Transaction::TYP_SHIP:
                                        $inStorage = $currentBase->getShipStorage($identifier);
                                        $currentBase->setShipStorage($identifier, $inStorage - $quantity);
                                        break;
                                    case Transaction::TYP_COMMANDER:
                                        if (($commander = $commanderManager->get($identifier)) !== null and $commander->getRPlayer() == $currentPlayer->getId() and Commander::ONSALE !== $commander->statement) {
                                            $commander->statement = Commander::ONSALE;
                                            $commanderManager->emptySquadrons($commander);
                                        } else {
                                            $valid = false;
                                        }
                                        break;
                                }

                                if ($valid) {
                                    // création de la transaction
                                    $tr = new Transaction();
                                    $tr->rPlayer = $currentPlayer->getid();
                                    $tr->rPlace = $currentBase->getid();
                                    $tr->type = $type;
                                    $tr->quantity = $quantity;
                                    $tr->identifier = $identifier;
                                    $tr->price = $price;
                                    $tr->commercialShipQuantity = $commercialShipQuantity;
                                    $tr->statement = Transaction::ST_PROPOSED;
                                    $tr->dPublication = Utils::now();
                                    $transactionManager->add($tr);

                                    // création du convoi
                                    $cs = new CommercialShipping();
                                    $cs->rPlayer = $currentPlayer->getid();
                                    $cs->rBase = $currentBase->getId();
                                    $cs->rBaseDestination = 0;
                                    $cs->rTransaction = $tr->id;
                                    $cs->resourceTransported = null;
                                    $cs->shipQuantity = $commercialShipQuantity;
                                    $cs->dDeparture = null;
                                    $cs->dArrival = null;
                                    $cs->statement = CommercialShipping::ST_WAITING;
                                    $commercialShippingManager->add($cs);

                                    $entityManager->flush();

                                    $this->addFlash('market_success', 'Votre proposition a été envoyée sur le marché.');

                                    return $this->redirect($request->headers->get('referer'));
                                } else {
                                    throw new ErrorException('Il y a un problème avec votre commandant.');
                                }
                            } else {
                                throw new FormException('Vous n\'avez pas assez de vaisseaux de transport disponibles.');
                            }
                        } else {
                            switch ($type) {
                                case Transaction::TYP_RESOURCE :
                                    throw new FormException('Vous n\'avez pas assez de ressources en stock.');
                                case Transaction::TYP_SHIP :
                                    throw new FormException('Vous n\'avez pas assez de vaisseaux.');
                                default:
                                    throw new ErrorException('Erreur pour une raison étrange, contactez un administrateur.');
                            }
                        }
                    } else {
                        throw new ErrorException('impossible de faire une proposition sur le marché !');
                    }
                }
            } else {
                throw new ErrorException('impossible de faire une proposition sur le marché');
            }
        } else {
            throw new FormException('pas assez d\'informations pour faire une proposition sur le marché');
        }
    }
}
