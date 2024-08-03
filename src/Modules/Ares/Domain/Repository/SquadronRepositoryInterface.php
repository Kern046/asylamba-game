<?php

namespace App\Modules\Ares\Domain\Repository;

use App\Modules\Ares\Model\Squadron;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<Squadron>
 */
interface SquadronRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return array{
	 *     nb0: int,
	 *     nb1: int,
	 *     nb2: int,
	 *     nb3: int,
	 *     nb4: int,
	 *     nb5: int,
	 *     nb6: int,
	 *     nb7: int,
	 *     nb8: int,
	 *     nb9: int,
	 *     nb10: int,
	 *     nb11: int,
	 * }
	 */
	public function getFactionFleetStats(Color $faction): array;
}
