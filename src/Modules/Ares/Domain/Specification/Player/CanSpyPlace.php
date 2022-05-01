<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Gaia\Model\Place;

class CanSpyPlace extends PlayerSpecification
{
	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return (0 != $candidate->rPlayer && $candidate->playerColor != $this->player->rColor) || (0 == $candidate->rPlayer && 1 == $candidate->typeOfPlace);
	}
}
