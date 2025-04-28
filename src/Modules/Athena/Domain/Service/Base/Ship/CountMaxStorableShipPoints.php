<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;

readonly class CountMaxStorableShipPoints
{
	public function __construct(private OrbitalBaseHelper $orbitalBaseHelper)
	{
	}

	public function __invoke(OrbitalBase $orbitalBase, DockType $dockType): int
	{
		return $this->orbitalBaseHelper->getBuildingInfo(
			$dockType->getBuildingNumber(),
			'level',
			$dockType->getLevel($orbitalBase),
			'storageSpace'
		) ?? 0;
	}
}
