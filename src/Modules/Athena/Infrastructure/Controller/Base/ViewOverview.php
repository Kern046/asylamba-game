<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base;

use App\Classes\Library\Game;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Athena\Manager\ShipQueueManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Resource\PlaceResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewOverview extends AbstractController
{
    public function __invoke(
        Request $request,
        OrbitalBase $orbitalBase,
        CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
        CommanderManager $commanderManager,
        CommercialRouteManager $commercialRouteManager,
        ShipQueueManager $shipQueueManager,
    ): Response {
        // @TODO: move it to the using part of the code and remove useless data
        if ($orbitalBase->getLevelSpatioport() > 0) {
            $orbitalBase->commercialRoutesData = $commercialRouteManager->getBaseCommercialData($orbitalBase);
        }
        if ($orbitalBase->getLevelDock1() > 0) {
            $orbitalBase->dock1ShipQueues = $shipQueueManager->getByBaseAndDockType($orbitalBase->getId(), 1);
        }
        if ($orbitalBase->getLevelDock2() > 0) {
            $orbitalBase->dock2ShipQueues = $shipQueueManager->getByBaseAndDockType($orbitalBase->getId(), 2);
        }

        return $this->render('pages/athena/overview.html.twig', [
            'commanders' => $commanderManager->getBaseCommanders($orbitalBase->getId(), [Commander::AFFECTED, Commander::MOVING]),
            'vanguard_positions' => PlaceResource::get($orbitalBase->typeOfBase, 'l-line-position'),
            'vanguard_positions_count' => PlaceResource::get($orbitalBase->typeOfBase, 'l-line'),
            'rear_positions' => PlaceResource::get($orbitalBase->typeOfBase, 'r-line-position'),
            'rear_positions_count' => PlaceResource::get($orbitalBase->typeOfBase, 'r-line'),
            'science_coeff' => Game::getImprovementFromScientificCoef($orbitalBase->getPlanetHistory()),
            'minimal_change_level' => $this->getParameter('athena.obm.change_type_min_level'),
            'capital_change_level' => $this->getParameter('athena.obm.capital_min_level'),
            'capitals_count' => $this->getCapitalsCount($currentPlayerBasesRegistry->all()),
            'building_resource_refund' => $this->getParameter('athena.building.building_queue_resource_refund'),
        ]);
    }

    private function getCapitalsCount(array $bases): int
    {
        return \count(\array_filter(
            $bases,
            fn (OrbitalBase $base) => $base->isCapital(),
        ));
    }
}
