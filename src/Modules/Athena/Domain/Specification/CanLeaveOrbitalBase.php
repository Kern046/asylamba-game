<?php

namespace App\Modules\Athena\Domain\Specification;

use App\Modules\Athena\Model\OrbitalBase;
use App\Shared\Domain\Specification\Specification;

class CanLeaveOrbitalBase implements Specification
{
	/**
	 * @param OrbitalBase $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		$diff = (new \DateTime())->diff($candidate->createdAt);

		return $diff->format('%a') > 1 || $diff->format('%H') >= OrbitalBase::COOL_DOWN;
	}
}
