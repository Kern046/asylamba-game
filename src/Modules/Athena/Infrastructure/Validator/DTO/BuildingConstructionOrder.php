<?php

namespace App\Modules\Athena\Infrastructure\Validator\DTO;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Promethee\Infrastructure\Validator\DTO\HasTechnology;
use App\Modules\Promethee\Model\Technology;

final readonly class BuildingConstructionOrder implements HasBuildingIdentifier, HasBase, HasTechnology
{
	public function __construct(
		private OrbitalBase $orbitalBase,
		private Technology $technology,
		private int $buildingIdentifier,
		private int $targetLevel,
	) {

	}

	public function getBase(): OrbitalBase
	{
		return $this->orbitalBase;
	}

	public function getBuildingIdentifier(): int
	{
		return $this->buildingIdentifier;
	}

	public function getTargetLevel(): int
	{
		return $this->targetLevel;
	}

	public function getTechnology(): Technology
	{
		return $this->technology;
	}
}
