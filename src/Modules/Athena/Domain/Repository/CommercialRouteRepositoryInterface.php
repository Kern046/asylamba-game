<?php

namespace App\Modules\Athena\Domain\Repository;

use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

interface CommercialRouteRepositoryInterface extends EntityRepositoryInterface
{
	public function get(int $id): CommercialRoute|null;

	/**
	 * @return list<CommercialRoute>
	 */
	public function searchCandidates(
		Player $player,
		OrbitalBase $orbitalBase,
		array $factions,
		int $minDistance,
		int $maxDistance,
	): array;

	/**
	 * @return list<CommercialRoute>
	 */
	public function getAllPlayerRoutes(Player $player): array;

	public function countCommercialRoutesBetweenFactions(Color $faction, Color $otherFaction): int;

	public function getCommercialRouteFactionData(Color $faction): array;

	public function getByIdAndBase(Uuid $id, OrbitalBase $base): CommercialRoute|null;

	public function getByIdAndDistantBase(Uuid $id, OrbitalBase $base): CommercialRoute|null;

	/**
	 * @return list<CommercialRoute>
	 */
	public function getByBase(OrbitalBase $base): array;

	/**
	 * @return list<CommercialRoute>
	 */
	public function getByDistantBase(OrbitalBase $base): array;

	public function getExistingRoute(OrbitalBase $base, OrbitalBase $distantBase): CommercialRoute|null;

	/**
	 * @return list<CommercialRoute>
	 */
	public function getBaseRoutes(OrbitalBase $base): array;

	public function getBaseIncome(OrbitalBase $base): int;

	/**
	 * @param list<int> $statements
	 */
	public function countBaseRoutes(OrbitalBase $base, array $statements): int;

	public function freezeRoutes(Color $faction, Color $otherFaction, bool $freeze): void;
}
