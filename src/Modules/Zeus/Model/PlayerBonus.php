<?php

/**
 * Player Bonus.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 18.07.13
 */

namespace App\Modules\Zeus\Model;

use App\Classes\Container\StackList;
use App\Modules\Promethee\Model\Technology;

class PlayerBonus
{
    public int $rPlayer;
    public Technology $technology;
    public StackList $bonuses;
    public int $playerColor;

    public function __construct(Player $player, Technology $technology)
    {
        $this->rPlayer = $player->id;
        $this->playerColor = $player->rColor;
        $this->technology = $technology;
        $this->bonuses = new StackList();
    }
}
