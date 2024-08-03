<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\RecyclingLog;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

interface RecyclingLogRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<RecyclingLog>
	 */
	public function getBaseActiveMissionsLogs(OrbitalBase $base): array;
}
