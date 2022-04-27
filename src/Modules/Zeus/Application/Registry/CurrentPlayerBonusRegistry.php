<?php

namespace App\Modules\Zeus\Application\Registry;

use App\Modules\Zeus\Model\PlayerBonus;

class CurrentPlayerBonusRegistry
{
    private PlayerBonus $playerBonus;

    public function setPlayerBonus(PlayerBonus $playerBonus): void
    {
        $this->playerBonus = $playerBonus;
    }

    public function getPlayerBonus(): PlayerBonus
    {
        return $this->playerBonus;
    }
}
