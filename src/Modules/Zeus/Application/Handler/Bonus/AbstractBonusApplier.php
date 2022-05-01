<?php

namespace App\Modules\Zeus\Application\Handler\Bonus;

use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\PlayerBonus;

abstract class AbstractBonusApplier implements BonusApplierInterface
{
	public function __construct(private readonly CurrentPlayerBonusRegistry $currentPlayerBonusRegistry)
	{
	}

	public function apply(float|int $value, int $bonusId, PlayerBonus $playerBonus = null): float
	{
		$playerBonus = $playerBonus ?? $this->currentPlayerBonusRegistry->getPlayerBonus();

		$bonusValue = $playerBonus->bonuses->get($bonusId);
		$newValue = $value * $bonusValue / 100;

		$this->postApply($bonusId, $bonusValue, $value, $newValue);

		return $newValue;
	}

	protected function postApply(int $bonusId, float $bonusValue, float|int $oldValue, float $newValue): void
	{
	}
}
