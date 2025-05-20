<?php

namespace App\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IsValidTargetLevelValidator extends ConstraintValidator
{
	public function __construct(
		private readonly BuildingLevelHandler $buildingLevelHandler,
		private readonly BuildingQueueRepositoryInterface $buildingQueueRepository,
	) {

	}

	/**
	 * @param BuildingConstructionOrder $value
	 */
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof IsValidTargetLevel) {
			throw new UnexpectedTypeException($constraint, IsValidTargetLevel::class);
		}

		if (!$value instanceof BuildingConstructionOrder) {
			throw new UnexpectedValueException($value, BuildingConstructionOrder::class);
		}

		$orbitalBase = $value->getBase();
		$buildingId = $value->getBuildingIdentifier();
		$level = $value->getTargetLevel();

		if (OrbitalBaseResource::GENERATOR === $buildingId) {
			if ($level > OrbitalBaseResource::$building[$buildingId]['maxLevel'][$orbitalBase->typeOfBase]) {
				$this->context
					->buildViolation('niveau maximum atteint')
					->addViolation();
			}
			return;
		}
		$realGeneratorLevel = $this->buildingLevelHandler->getBuildingRealLevel(
			$orbitalBase,
			OrbitalBaseResource::GENERATOR,
			$this->buildingQueueRepository->getBaseQueues($orbitalBase),
		);
		$requiredGeneratorLevel = $this->buildingLevelHandler->getRequiredGeneratorLevel($buildingId);

		if ($level > OrbitalBaseResource::$building[$buildingId]['maxLevel'][$orbitalBase->typeOfBase]) {
			$this->context
				->buildViolation('niveau maximum atteint')
				->addViolation();
		} elseif ($level > ($realGeneratorLevel - $requiredGeneratorLevel)) {
			$this->context
				->buildViolation('le niveau du générateur n\'est pas assez élevé')
				->addViolation();
		}
	}
}
