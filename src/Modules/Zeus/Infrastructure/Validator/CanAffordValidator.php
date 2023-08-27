<?php

namespace App\Modules\Zeus\Infrastructure\Validator;

use App\Modules\Ares\Infrastructure\Validator\DTO\HasCommander;
use App\Modules\Ares\Model\Commander;
use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Model\CreditHolderInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CanAffordValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof CanAfford) {
			throw new UnexpectedTypeException($constraint, CanAfford::class);
		}

		$creditHolder = $this->getCreditHolderFrom($value);

		if (!$creditHolder->canAfford($constraint->price)) {
			$this->context
				->buildViolation('Vous n\'avez pas assez de crédits pour conquérir cette base.')
				->addViolation();
		}
	}

	private function getCreditHolderFrom($value): CreditHolderInterface
	{
		return match (true) {
			$value instanceof HasCommander => $value->getCommander()->player,
			$value instanceof Commander => $value->player,
			$value instanceof Player, $value instanceof Color => $value,
			default => throw new UnexpectedTypeException($value, CreditHolderInterface::class),
		};
	}
}
