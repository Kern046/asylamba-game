<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

interface ShipQueueRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): ShipQueue|null;

	/**
	 * @return list<ShipQueue>
	 */
	public function getAll(): array;

	/**
	 * @return list<ShipQueue>
	 */
	public function getBaseQueues(OrbitalBase $base): array;

	/**
	 * @return list<ShipQueue>
	 */
	public function getByBaseAndDockType(OrbitalBase $base, int $dockType): array;
}
