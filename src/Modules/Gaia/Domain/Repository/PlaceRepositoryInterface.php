<?php

namespace App\Modules\Gaia\Domain\Repository;

use App\Modules\Gaia\Model\Place;
use App\Modules\Gaia\Model\Sector;
use App\Modules\Gaia\Model\System;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends EntityRepositoryInterface<Place>
 */
interface PlaceRepositoryInterface extends EntityRepositoryInterface
{
	public function get(Uuid $id): Place|null;

	/**
	 * @param list<Uuid> $ids
	 * @return list<Place>
	 */
	public function getByIds(array $ids): array;

	/**
	 * @return list<Place>
	 */
	public function getSystemPlaces(System $system): array;

	/**
	 * @return list<Place>
	 */
	public function getPlayerPlaces(int $offset, int $limit = 20): array;

	public function countPlayerPlaces(): int;
	/**
	 * @return list<Place>
	 */
	public function getNpcPlaces(int $offset, int $limit = 20): array;

	public function countNpsPlaces(): int;

	/**
	 * @return list<Uuid>
	 */
	public function findPlacesIdsForANewBase(Sector $sector): array;

	/**
	 * @return list<Place>
	 */
	public function search(string $search): array;

	public function npcQuickfix(): void;
}
