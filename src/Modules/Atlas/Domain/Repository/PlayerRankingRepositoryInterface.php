<?php

namespace App\Modules\Atlas\Domain\Repository;

use App\Modules\Atlas\Model\PlayerRanking;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<PlayerRanking>
 */
interface PlayerRankingRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<PlayerRanking>
	 */
	public function getFactionPlayerRankings(Color $faction): array;

	/**
	 * @return array{player_id: int, lostPEV: int, destroyedPEV: int}
	 */
	public function getAttackersButcherRanking(): array;

	/**
	 * @return array{player_id: int, lostPEV: int, destroyedPEV: int, score: int}
	 */
	public function getDefendersButcherRanking(): array;
}
