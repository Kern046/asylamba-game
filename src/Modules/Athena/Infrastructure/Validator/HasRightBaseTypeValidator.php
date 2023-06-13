<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class HasRightBaseTypeValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof HasRightBaseType) {
			throw new UnexpectedTypeException($constraint, HasRightBaseType::class);
		}

		if (!$value instanceof BuildingConstructionOrder) {
			throw new UnexpectedValueException($value, BuildingConstructionOrder::class);
		}

		$orbitalBase = $value->getBase();
		$buildingId = $value->getBuildingIdentifier();
		$level = $value->getTargetLevel();

		if (1 === $level && OrbitalBase::TYP_NEUTRAL === $orbitalBase->typeOfBase && in_array($buildingId, [OrbitalBaseResource::SPATIOPORT, OrbitalBaseResource::DOCK2])) {
			$this->context
				->buildViolation('vous devez évoluer votre colonie pour débloquer ce bâtiment')
				->addViolation();
		}
	}
}
