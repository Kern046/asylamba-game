<?php

declare(strict_types=1);

namespace App\Modules\Ares\Application\Handler;

use App\Modules\Ares\Model\Commander;

readonly class FightImportanceHandler
{
	public function __construct(
		private CommanderArmyHandler $commanderArmyHandler,
	) {
	}

	public function calculateImportance(Commander $attacker, Commander $defender): int
	{
		$attackerPev = $this->commanderArmyHandler->getPev($attacker);
		$defenderPev = $this->commanderArmyHandler->getPev($defender);

		return intval(floor(
			(($attackerPev + 1) * $defenderPev)
			/ (
				($attackerPev + 1) *
				(($defender->level + 1) / ($attacker->level + 1))
			)
		));
	}
}
