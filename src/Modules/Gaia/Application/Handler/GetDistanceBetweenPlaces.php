<?php

namespace App\Modules\Gaia\Application\Handler;

use App\Classes\Library\Game;
use App\Modules\Gaia\Model\Place;

class GetDistanceBetweenPlaces
{
	public function __invoke(Place $from, Place $to): float
	{
		return Game::getDistance(
			$from->system->xPosition,
			$to->system->xPosition,
			$from->system->xPosition,
			$to->system->xPosition
		);
	}
}
