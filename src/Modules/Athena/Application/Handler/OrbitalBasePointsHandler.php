<?php

namespace App\Modules\Athena\Application\Handler;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;

readonly class OrbitalBasePointsHandler
{
	public function __construct(
		private BuildingLevelHandler $buildingLevelHandler,
		private OrbitalBaseHelper $orbitalBaseHelper,
	) {

	}

	public function updatePoints(OrbitalBase $orbitalBase): int
	{
		$initialPoints = $orbitalBase->points;
		$points = 0;

		for ($i = 0; $i < OrbitalBaseResource::BUILDING_QUANTITY; ++$i) {
			for ($j = 0; $j < $this->buildingLevelHandler->getBuildingLevel($orbitalBase, $i); ++$j) {
				$points += $this->orbitalBaseHelper->getBuildingInfo($i, 'level', $j + 1, 'resourcePrice') / 1000;
			}
		}

		$points = round($points);
		$orbitalBase->points = $points;

		return $points - $initialPoints;
	}
}
