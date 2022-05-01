<?php

namespace App\Modules\Zeus\Application\Handler\Bonus;

use App\Modules\Zeus\Model\PlayerBonus;

interface BonusApplierInterface
{
	public function apply(int|float $value, int $bonusId, PlayerBonus $playerBonus = null): float;
}
