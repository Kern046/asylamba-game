<?php

namespace App\Modules\Atlas\Domain\Repository;

use App\Modules\Atlas\Model\Ranking;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<Ranking>
 */
interface RankingRepositoryInterface extends EntityRepositoryInterface
{
	public function hasBeenAlreadyProcessed(bool $isPlayer, bool $isFaction): bool;
}
