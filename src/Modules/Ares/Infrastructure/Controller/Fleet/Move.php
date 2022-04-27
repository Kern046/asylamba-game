<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Game;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Manager\SectorManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Move extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        CommanderManager $commanderManager,
        PlaceManager $placeManager,
        SectorManager $sectorManager,
        EntityManager $entityManager,
        int $id,
    ): Response {
        $session = $request->getSession();

        if (($commander = $commanderManager->get($id)) !== null && $commander->rPlayer === $currentPlayer->getId()) {
            if (($place = $placeManager->get($request->query->getInt('placeId'))) !== null) {
                if ($commander->playerColor == $place->playerColor) {
                    $home = $placeManager->get($commander->getRBase());

                    $length = Game::getDistance($home->getXSystem(), $place->getXSystem(), $home->getYSystem(), $place->getYSystem());
                    $duration = Game::getTimeToTravel($home, $place, $session->get('playerBonus'));

                    if (Commander::AFFECTED === $commander->statement) {
                        $sector = $sectorManager->get($place->rSector);
                        $isFactionSector = ($sector->rColor == $commander->playerColor) ? true : false;

                        $commander->destinationPlaceName = $place->baseName;

                        if ($length <= Commander::DISTANCEMAX || $isFactionSector) {
                            $commanderManager->move($commander, $place->getId(), $commander->rBase, Commander::MOVE, $length, $duration);

                            //								if (true === $container->getParameter('data_analysis')) {
                            //									$qr = $database->prepare('INSERT INTO
                            //								DA_CommercialRelation(`from`, `to`, type, weight, dAction)
                            //								VALUES(?, ?, ?, ?, ?)'
                            //									);
                            //									$ships = $commander->getNbrShipByType();
                            //									$price = 0;
//
                            //									for ($i = 0; $i < count($ships); $i++) {
                            //										$price += DataAnalysis::resourceToStdUnit(ShipResource::getInfo($i, 'resourcePrice') * $ships[$i]);
                            //									}
//
                            //									$qr->execute([$commander->rPlayer, $place->rPlayer, 7, $price, Utils::now()]);
                            //								}
                            $entityManager->flush();

                            return $this->redirect($request->headers->get('referer'));
                        } else {
                            throw new ErrorException('Cet emplacement est trop éloigné.');
                        }
                    } else {
                        throw new ErrorException('Cet officier est déjà en déplacement.');
                    }
                } else {
                    throw new ErrorException('Vous ne pouvez pas envoyer une flotte sur une planète qui ne vous appartient pas.');
                }
            } else {
                throw new ErrorException('Ce lieu n\'existe pas.');
            }
        } else {
            throw new ErrorException('Ce commandant ne vous appartient pas ou n\'existe pas.');
        }
    }
}
