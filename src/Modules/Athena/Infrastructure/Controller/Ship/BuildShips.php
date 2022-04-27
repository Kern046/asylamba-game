<?php

namespace App\Modules\Athena\Infrastructure\Controller\Ship;

use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Format;
use App\Classes\Library\Utils;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Helper\ShipHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\ShipQueueManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BuildShips extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        OrbitalBase $currentBase,
        ColorManager $colorManager,
        OrbitalBaseManager $orbitalBaseManager,
        OrbitalBaseHelper $orbitalBaseHelper,
        ShipQueueManager $shipQueueManager,
        ShipHelper $shipHelper,
        TechnologyManager $technologyManager,
    ): Response {
        $session = $request->getSession();
        $ship = $request->query->get('ship');
        $quantity = $request->query->get('quantity');

        if (false !== $ship and false !== $quantity and 0 != $quantity) {
            if (ShipResource::isAShip($ship)) {
                if ($orbitalBaseHelper->isAShipFromDock1($ship)) {
                    $dockType = 1;
                } elseif ($orbitalBaseHelper->isAShipFromDock2($ship)) {
                    $dockType = 2;
                    $quantity = 1;
                } else {
                    $dockType = 3;
                    $quantity = 1;
                }
                $shipQueues = $shipQueueManager->getByBaseAndDockType($currentBase->getId(), $dockType);
                $nbShipQueues = count($shipQueues);
                $technos = $technologyManager->getPlayerTechnology($currentPlayer->getId());
                if ($shipHelper->haveRights($ship, 'resource', $currentBase->getResourcesStorage(), $quantity)
                    and $shipHelper->haveRights($ship, 'queue', $currentBase, $nbShipQueues)
                    and $shipHelper->haveRights($ship, 'shipTree', $currentBase)
                    and $shipHelper->haveRights($ship, 'pev', $currentBase, $quantity)
                    and $shipHelper->haveRights($ship, 'techno', $technos)) {
                    // construit le(s) nouveau(x) vaisseau(x)
                    $sq = new ShipQueue();
                    $sq->rOrbitalBase = $currentBase->getId();
                    $sq->dockType = $dockType;
                    $sq->shipNumber = $ship;
                    $sq->quantity = $quantity;

                    $time = ShipResource::getInfo($ship, 'time') * $quantity;
                    switch ($dockType) {
                        case 1:
                            $playerBonus = PlayerBonus::DOCK1_SPEED;
                            break;
                        case 2:
                            $playerBonus = PlayerBonus::DOCK2_SPEED;
                            break;
                        case 3:
                            $playerBonus = PlayerBonus::DOCK3_SPEED;
                            break;
                    }
                    $bonus = $time * $session->get('playerBonus')->get($playerBonus) / 100;

                    $sq->dStart = (0 === $nbShipQueues) ? Utils::now() : $shipQueues[$nbShipQueues - 1]->dEnd;
                    $sq->dEnd = Utils::addSecondsToDate($sq->dStart, round($time - $bonus));

                    $shipQueueManager->add($sq, $currentPlayer);

                    // débit des ressources au joueur
                    $resourcePrice = ShipResource::getInfo($ship, 'resourcePrice') * $quantity;
                    if (ShipResource::CERBERE == $ship || ShipResource::PHENIX == $ship) {
                        if (in_array(ColorResource::PRICEBIGSHIPBONUS, $colorManager->get($currentPlayer->getRColor())->bonus)) {
                            $resourcePrice -= round($resourcePrice * ColorResource::BONUS_EMPIRE_CRUISER / 100);
                        }
                    }
                    $orbitalBaseManager->decreaseResources($currentBase, $resourcePrice);

                    // ajout de l'event dans le contrôleur
                    $session->get('playerEvent')->add($sq->dEnd, $this->getParameter('event_base'), $currentBase->getId());

                    //						if (true === $this->getContainer()->getParameter('data_analysis')) {
                    //							$qr = $database->prepare('INSERT INTO
                    //						DA_BaseAction(`from`, type, opt1, opt2, weight, dAction)
                    //						VALUES(?, ?, ?, ?, ?, ?)'
                    //							);
                    //							$qr->execute([$session->get('playerId'), 3, $ship, $quantity, DataAnalysis::resourceToStdUnit(ShipResource::getInfo($ship, 'resourcePrice') * $quantity), Utils::now()]);
                    //						}

                    // alerte
                    if (1 == $quantity) {
                        $this->addFlash('success', 'Construction d\''.(ShipResource::isAFemaleShipName($ship) ? 'une ' : 'un ').ShipResource::getInfo($ship, 'codeName').' commandée');
                    } else {
                        $this->addFlash('success', 'Construction de '.$quantity.' '.ShipResource::getInfo($ship, 'codeName').Format::addPlural($quantity).' commandée');
                    }

                    return $this->redirect($request->headers->get('referer'));
                } else {
                    throw new ErrorException('les conditions ne sont pas remplies pour construire ce vaisseau');
                }
            } else {
                throw new ErrorException('construction de vaisseau impossible - vaisseau inconnu');
            }
        } else {
            throw new FormException('pas assez d\'informations pour construire un vaisseau');
        }
    }
}
