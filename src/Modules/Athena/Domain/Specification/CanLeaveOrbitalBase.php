<?php

namespace App\Modules\Athena\Domain\Specification;

use App\Modules\Athena\Model\OrbitalBase;
use App\Shared\Domain\Specification\Specification;

readonly class CanLeaveOrbitalBase implements Specification
{
	public function __construct(private int $coolDownHours)
	{
	}

	/**
	 * @param OrbitalBase $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		$diff = (new \DateTime())->diff($candidate->createdAt);

		return $diff->format('%a') > 1 || $diff->format('%H') >= $this->coolDownHours;
	}
}
