<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\RecyclingLogManager;
use App\Modules\Athena\Manager\RecyclingMissionManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewRecycling extends AbstractController
{
	public function __invoke(
		OrbitalBase $currentBase,
		OrbitalBaseHelper $orbitalBaseHelper,
		RecyclingMissionManager $recyclingMissionManager,
		RecyclingLogManager $recyclingLogManager,
	): Response {
		// load recycling missions
		$baseMissions = $recyclingMissionManager->getBaseActiveMissions($currentBase->rPlace);
		$missionsLogs = $recyclingLogManager->getBaseActiveMissionsLogs($currentBase->rPlace);
		$missionQuantity = count($baseMissions);

		$totalRecyclers = $orbitalBaseHelper->getBuildingInfo(
			OrbitalBaseResource::RECYCLING,
			'level',
			$currentBase->levelRecycling,
			'nbRecyclers'
		);
		$busyRecyclers = 0;

		foreach ($baseMissions as $mission) {
			$busyRecyclers += $mission->recyclerQuantity;
			$busyRecyclers += $mission->addToNextMission;
		}

		$freeRecyclers = $totalRecyclers - $busyRecyclers;

		return $this->render('pages/athena/base/building/recycling.html.twig', [
			'base_missions' => array_map(fn (RecyclingMission $rm) => $this->getData($rm), $baseMissions),
			'mission_logs' => $missionsLogs,
			'mission_quantity' => $missionQuantity,
			'free_recyclers' => $freeRecyclers,
			'busy_recyclers' => $busyRecyclers,
			'total_recyclers' => $totalRecyclers,
		]);
	}

	private function getData(RecyclingMission $mission): array
	{
		// usefull vars
		$missionID = md5($mission->id.$mission->recyclerQuantity);
		$missionID = strtoupper(substr($missionID, 0, 3).'-'.substr($missionID, 3, 6).'-'.substr($missionID, 10, 2));

		// @TODO Infamous patch
		$percent = Utils::interval(Utils::now(), date('Y-m-d H:i:s', strtotime($mission->uRecycling) - $mission->cycleTime), 's') / $mission->cycleTime * 100;
		$travelTime = ($mission->cycleTime - RecyclingMission::RECYCLING_TIME) / 2;
		$beginRECY = Format::percent($travelTime, $mission->cycleTime);
		$endRECY = Format::percent($travelTime + RecyclingMission::RECYCLING_TIME, $mission->cycleTime);

		return [
			'mission' => $mission,
			'mission_id' => $missionID,
			'percent' => $percent,
			'travel_time' => $travelTime,
			'begin_recv' => $beginRECY,
			'end_recv' => $endRECY,
			'coords' => Game::formatCoord($mission->xSystem, $mission->ySystem, $mission->position, $mission->sectorId),
		];
	}
}
