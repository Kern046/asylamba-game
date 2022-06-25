<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Gaia\Model\Place;

class CanRecycle extends PlayerSpecification
{
	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return null === $candidate->system->sector->faction
			|| $candidate->system->sector->faction->id === $this->player->faction->id;
	}
}
