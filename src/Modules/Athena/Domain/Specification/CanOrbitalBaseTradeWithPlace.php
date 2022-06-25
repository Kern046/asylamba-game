<?php

namespace App\Modules\Athena\Domain\Specification;

use App\Modules\Gaia\Model\Place;

class CanOrbitalBaseTradeWithPlace extends OrbitalBaseSpecification
{
	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return null !== $candidate->player && $candidate->id !== $this->orbitalBase->place->id;
	}
}
