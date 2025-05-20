<?php

namespace App\Modules\Ares\Infrastructure\Validator\Commander;

use App\Modules\Ares\Infrastructure\Validator\DTO\HasCommander;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IsInOrbitValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof IsInOrbit) {
			throw new UnexpectedTypeException($constraint, IsInOrbit::class);
		}

		if (!$value instanceof HasCommander) {
			throw new UnexpectedValueException($value, HasCommander::class);
		}

		$commander = $value->getCommander();

		if (!$commander->isAffected()) {
			$this->context
				->buildViolation('Cet officier est déjà en déplacement.')
				->addViolation();
		}
	}
}
