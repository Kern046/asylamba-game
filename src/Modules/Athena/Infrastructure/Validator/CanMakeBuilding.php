<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\SequentiallyValidator;

class CanMakeBuilding extends Sequentially
{
	public function __construct(
		int $buildingQueuesCount,
	) {
		parent::__construct([
			'constraints' => [
				new HasRightBaseType(),
				new HasUnlockedBuilding(),
				new IsValidTargetLevel(),
				new CanOrderBuilding($buildingQueuesCount),
			],
		]);
	}

	public function validatedBy(): string
	{
		return SequentiallyValidator::class;
	}
}
