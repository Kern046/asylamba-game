<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class HasResourcesForBuildingValidator extends ConstraintValidator
{
	public function __construct(
		private readonly OrbitalBaseHelper $orbitalBaseHelper,
	) {

	}

	/**
	 * @param BuildingConstructionOrder $value
	 */
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof HasResourcesForBuilding) {
			throw new UnexpectedTypeException($constraint, HasResourcesForBuilding::class);
		}

		if (!$value instanceof BuildingConstructionOrder) {
			throw new UnexpectedValueException($value, BuildingConstructionOrder::class);
		}

		$orbitalBase = $value->getBase();

		$neededResources = $this->orbitalBaseHelper->getBuildingInfo(
			$value->getBuildingIdentifier(),
			'level',
			$value->getTargetLevel(),
			'resourcePrice',
		);

		if ($orbitalBase->resourcesStorage < $neededResources) {
			$this->context->buildViolation('Cette base ne dispose pas de suffisamment de ressources')
				->addViolation();
		}
	}
}
