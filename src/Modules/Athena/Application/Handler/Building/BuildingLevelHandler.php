<?php

namespace App\Modules\Athena\Application\Handler\Building;

use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BuildingLevelHandler
{
	public function increaseBuildingLevel(OrbitalBase $orbitalBase, int $buildingIdentifier): void
	{
		$this->updateBuildingLevel($orbitalBase, $buildingIdentifier, fn (int $level) => $level + 1);
	}

	public function decreaseBuildingLevel(OrbitalBase $orbitalBase, int $buildingIdentifier): void
	{
		$this->updateBuildingLevel($orbitalBase, $buildingIdentifier, fn (int $level) => max($level - 1, 0));
	}

	private function updateBuildingLevel(OrbitalBase $orbitalBase, int $buildingIdentifier, callable $updateLevel): void
	{
		$propertyAccessor = PropertyAccess::createPropertyAccessor();
		$levelField = $this->getBuildingLevelField($buildingIdentifier);

		$propertyAccessor->setValue(
			$orbitalBase,
			$levelField,
			$updateLevel($propertyAccessor->getValue($orbitalBase, $levelField)),
		);
	}

	public function getBuildingLevel(OrbitalBase $base, int $buildingIdentifier): int
	{
		$propertyAccessor = PropertyAccess::createPropertyAccessor();

		return $propertyAccessor->getValue($base, $this->getBuildingLevelField($buildingIdentifier));
	}

	public function getBuildingRealLevel(OrbitalBase $base, int $buildingIdentifier, array $buildingQueues): int
	{
		return array_reduce(
			$buildingQueues,
			function (int $level, BuildingQueue $buildingQueue) use ($buildingIdentifier) {
				if ($buildingIdentifier !== $buildingQueue->buildingNumber) {
					return $level;
				}

				return ($level < $buildingQueue->targetLevel) ? $buildingQueue->targetLevel : $level;
			},
			$this->getBuildingLevel($base, $buildingIdentifier),
		);
	}

	private function getBuildingLevelField(int $buildingIdentifier): string
	{
		$buildingName = OrbitalBaseResource::$building[$buildingIdentifier]['name']
			?? throw new \LogicException(sprintf('Building identifier %s is not valid', $buildingIdentifier));

		return sprintf('level%s', ucfirst($buildingName));
	}
}
