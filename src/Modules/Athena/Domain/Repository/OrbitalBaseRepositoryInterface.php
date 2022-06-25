<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Model\Place;
use App\Modules\Gaia\Model\Sector;
use App\Modules\Gaia\Model\System;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

interface OrbitalBaseRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): OrbitalBase|null;

    public function getPlaceBase(Place $place): OrbitalBase|null;

	/**
	 * @return list<OrbitalBase>
	 */
	public function getAll(): array;

	/**
	 * @return list<OrbitalBase>
	 */
	public function getPlayerBases(Player $player): array;

	public function getPlayerBasesCount(Player $player): int;

	/**
	 * @return list<OrbitalBase>
	 */
	public function getSectorBases(Sector $sector): array;

	/**
	 * @return list<OrbitalBase>
	 */
	public function getSystemBases(System $system): array;

	public function getPlayerBase(int $id, Player $player): OrbitalBase|null;
}
