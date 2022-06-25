<?php

namespace App\Modules\Promethee\Domain\Repository;

use App\Modules\Promethee\Model\Technology;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;

/**
 * @extends EntityRepositoryInterface<Technology>
 */
interface TechnologyRepositoryInterface extends EntityRepositoryInterface
{
	public function getPlayerTechnology(Player $player): Technology;
}
