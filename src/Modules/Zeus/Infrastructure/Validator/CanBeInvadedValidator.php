<?php

namespace App\Modules\Zeus\Infrastructure\Validator;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CanBeInvadedValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint): void
	{
		if (!$constraint instanceof CanBeInvaded) {
			throw new UnexpectedTypeException($constraint, CanBeInvaded::class);
		}

		if (!$constraint->targetedPlayer instanceof Player) {
			throw new UnexpectedValueException($constraint->targetedPlayer, Player::class);
		}

		if ($constraint->targetedPlayer->level <= 3 && !in_array($constraint->targetedPlayer->statement, [Player::DELETED, Player::DEAD])) {
			$this->context
				->buildViolation('Vous ne pouvez pas conquérir un joueur de niveau 3 ou moins.')
				->addViolation();
		}
	}
}
