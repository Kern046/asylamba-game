<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class HasFreeBuildingSlotsValidator extends ConstraintValidator
{
	/**
	 * @param BuildingConstructionOrder $value
	 */
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof HasFreeBuildingSlots) {
			throw new UnexpectedTypeException($constraint, HasFreeBuildingSlots::class);
		}

		if (!$value instanceof BuildingConstructionOrder) {
			throw new UnexpectedValueException($value, BuildingConstructionOrder::class);
		}

		$orbitalBase = $value->getBase();

		$maxQueues = OrbitalBaseResource::$building[OrbitalBaseResource::GENERATOR]['level'][$orbitalBase->levelGenerator - 1][3];

		if ($constraint->buildingQueuesCount >= $maxQueues) {
			$this->context->buildViolation('La file d\'attente est pleine')
				->addViolation();
		}
	}
}
