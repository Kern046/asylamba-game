<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<RecyclingMission>
 */
interface RecyclingMissionRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): RecyclingMission|null;

	/**
	 * @return list<RecyclingMission>
	 */
	public function getAll(): array;

	/**
	 * @return list<RecyclingMission>
	 */
	public function getBaseMissions(OrbitalBase $base): array;

	/**
	 * @return list<RecyclingMission>
	 */
	public function getBaseActiveMissions(OrbitalBase $base): array;

	public function removeBaseMissions(OrbitalBase $base): void;
}
