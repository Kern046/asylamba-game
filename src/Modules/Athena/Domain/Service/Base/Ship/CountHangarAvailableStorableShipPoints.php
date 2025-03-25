<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Model\OrbitalBase;

class CountHangarAvailableStorableShipPoints
{
	public function __construct(
		private CountQueuedShipPoints      $countQueuedShipPoints,
		private CountStoredShipPoints      $countStoredShipPoints,
		private CountMaxStorableShipPoints $countMaxStorableShipPoints,
	) {
	}

	public function __invoke(OrbitalBase $orbitalBase, array $shipQueues, DockType $dockType): int
	{
		$maxStorableShipPoints = ($this->countMaxStorableShipPoints)($orbitalBase, $dockType);
		$storedShipPoints = ($this->countStoredShipPoints)($orbitalBase, $dockType);
		$queuedShipPoints = ($this->countQueuedShipPoints)($shipQueues);

		return $maxStorableShipPoints - $storedShipPoints - $queuedShipPoints;
	}
}
