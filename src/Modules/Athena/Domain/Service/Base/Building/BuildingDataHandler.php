<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Building;

use App\Modules\Athena\Helper\OrbitalBaseHelper;

readonly class BuildingDataHandler
{
	public function __construct(
		private OrbitalBaseHelper $orbitalBaseHelper,
	) {

	}

	public function getBuildingName(int $identifier): string
	{
		return $this->orbitalBaseHelper->getBuildingInfo($identifier, 'name');
	}

	public function getBuildingColumn(int $identifier): string
	{
		return $this->orbitalBaseHelper->getBuildingInfo($identifier, 'column');
	}

	public function getFrenchName(int $identifier): string
	{
		return $this->orbitalBaseHelper->getBuildingInfo($identifier, 'frenchName');
	}

	public function getImageLink(int $identifier): string
	{
		return $this->orbitalBaseHelper->getBuildingInfo($identifier, 'imageLink');
	}

	public function getDescription(int $identifier): string
	{
		return $this->orbitalBaseHelper->getBuildingInfo($identifier, 'description');
	}

	public function getBuildingTimeCost(int $identifier, int $targetLevel): int|null
	{
		return $this->orbitalBaseHelper->getBuildingInfo($identifier, 'level', $targetLevel, 'time');
	}

	public function getBuildingResourceCost(int $identifier, int $targetLevel): int|null
	{
		return $this->orbitalBaseHelper->getBuildingInfo($identifier, 'level', $targetLevel, 'resourcePrice');
	}
}
