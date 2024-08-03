<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Specification;

use App\Modules\Zeus\Infrastructure\Validator\IsPlayerAlive;
use App\Shared\Domain\Specification\AndSpecification;

class CanEarnSchoolExperience extends AndSpecification
{
	public function __construct()
	{
		parent::__construct(
			new IsCommanderInSchool(),
			new IsPlayerAlive(),
		);
	}
}
