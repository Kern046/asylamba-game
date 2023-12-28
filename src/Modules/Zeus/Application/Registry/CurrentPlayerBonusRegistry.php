<?php

namespace App\Modules\Zeus\Application\Registry;

use App\Modules\Zeus\Model\PlayerBonus;

class CurrentPlayerBonusRegistry
{
	private PlayerBonus $playerBonus;
	private bool $isInitialized = false;

	public function setPlayerBonus(PlayerBonus $playerBonus): void
	{
		$this->playerBonus = $playerBonus;
		$this->isInitialized = true;
	}

	public function getPlayerBonus(): PlayerBonus|null
	{
		return $this->playerBonus ?? null;
	}

	public function isInitialized(): bool
	{
		return $this->isInitialized;
	}
}
