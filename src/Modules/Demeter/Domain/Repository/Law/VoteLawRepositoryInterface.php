<?php

namespace App\Modules\Demeter\Domain\Repository\Law;

use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Model\Law\VoteLaw;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;

/**
 * @extends EntityRepositoryInterface<VoteLaw>
 */
interface VoteLawRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<VoteLaw>
	 */
	public function getLawVotes(Law $law): array;

	public function hasVoted(Player $player, Law $law): bool;
}
