<?php

namespace App\Modules\Promethee\Infrastructure\Validator;

use App\Modules\Promethee\Infrastructure\Validator\DTO\HasTechnology;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class HasUnlockedTechnologyValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof HasUnlockedTechnology) {
			throw new UnexpectedTypeException($constraint, HasUnlockedTechnology::class);
		}

		if (!$value instanceof HasTechnology) {
			throw new UnexpectedValueException($value, HasTechnology::class);
		}

		$technology = $value->getTechnology();

		if (1 !== $technology->getTechnology($constraint->technologyId)) {
			$this->context
				->buildViolation('Vous devez débloquer la technologie de conquête.')
				->addViolation();
		}
	}
}
