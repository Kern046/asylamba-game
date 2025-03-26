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
use App\Modules\Demeter\Model\Color;
use App\Modules\Promethee\Model\Technology;

class PlayerBonus
{
	public int $rPlayer;
	public StackList $bonuses;
	public Color $playerColor;

	public function __construct(Player $player, public Technology $technology)
	{
		$this->rPlayer = $player->id;
		$this->playerColor = $player->faction
			?? throw new \LogicException(sprintf('Player %s faction cannot be null', $player->name));
		$this->bonuses = new StackList();
	}
}
