<?php

namespace App\Modules\Gaia\Ui\Http;

use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Manager\ConquestManager;
use App\Modules\Ares\Manager\LiveReportManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Artemis\Manager\SpyReportManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\RecyclingMissionManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Manager\SystemManager;
use App\Modules\Gaia\Model\Place;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoadSystem extends AbstractController
{
    public function __invoke(
        Request $request,
        OrbitalBase $currentBase,
        Player $currentPlayer,
        CommanderManager $commanderManager,
        SystemManager $systemManager,
        PlaceManager $placeManager,
        TechnologyManager $technologyManager,
        SpyReportManager $spyReportManager,
        LiveReportManager $liveReportManager,
        ConquestManager $conquestManager,
        OrbitalBaseManager $orbitalBaseManager,
        RecyclingMissionManager $recyclingMissionManager,
        int $id
    ): Response {
        if (null === ($system = $systemManager->get($id))) {
            throw new NotFoundHttpException('System not found');
        }
        // objet place
        $places = $placeManager->getSystemPlaces($system);

        $movingCommanders = $commanderManager->getPlayerCommanders($currentPlayer->getId(), [Commander::MOVING]);

        $placesIds = array_map(fn (Place $place) => $place->id, $places);

        $spyReportManager->newSession();
        $spyReportManager->load(['rPlayer' => $currentPlayer->getId(), 'rPlace' => $placesIds], ['dSpying', 'DESC'], [0, 30]);

        $basesCount = $orbitalBaseManager->getPlayerBasesCount($movingCommanders);

        return $this->render('components/map/system_details.html.twig', [
            'system' => $system,
            'places' => $places,
            'moving_commanders' => $movingCommanders,
            'technologies' => $technologyManager->getPlayerTechnology($currentPlayer->getId()),
            'recycling_missions' => $recyclingMissionManager->getBaseActiveMissions($currentBase->rPlace),
            'spy_reports' => $spyReportManager->getAll(),
            'combat_reports' => $liveReportManager->getAttackReportsByPlaces($currentPlayer->getId(), $placesIds),
            'colonization_cost' => $conquestManager->getColonizationCost($currentPlayer, $basesCount),
            'conquest_cost' => $conquestManager->getConquestCost($currentPlayer, $basesCount),
            'route_sector_bonus' => $this->getParameter('athena.trade.route.sector_bonus'),
            'route_color_bonus' => $this->getParameter('athena.trade.route.color_bonus'),
        ]);
    }
}
