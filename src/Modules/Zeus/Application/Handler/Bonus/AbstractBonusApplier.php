<?php

namespace App\Modules\Zeus\Application\Handler\Bonus;

use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\PlayerBonus;

abstract class AbstractBonusApplier implements BonusApplierInterface
{
	public function __construct(private readonly CurrentPlayerBonusRegistry $currentPlayerBonusRegistry)
	{
	}

	public function apply(float|int $initialValue, int $modifierId, PlayerBonus $playerBonus = null): float
	{
		$playerBonus = $playerBonus
			?? $this->currentPlayerBonusRegistry->getPlayerBonus()
			?? throw new \LogicException('Could not retrieve player bonus');

		$modifierValue = $playerBonus->bonuses->get($modifierId);
		$modifiedValue = PercentageApplier::toFloat($initialValue, $modifierValue);

		$this->postApply($modifierId, $modifierValue, $initialValue, $modifiedValue);

		return $modifiedValue;
	}

	protected function postApply(int $modifierId, float $modifierValue, float|int $initialValue, float $modifiedValue): void
	{
	}
}
