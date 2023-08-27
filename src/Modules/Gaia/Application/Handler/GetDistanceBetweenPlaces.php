<?php

namespace App\Modules\Gaia\Application\Handler;

use App\Classes\Library\Game;
use App\Modules\Gaia\Model\Place;

readonly class GetDistanceBetweenPlaces
{
	public function __invoke(Place $from, Place $to): float
	{
		return $this->getDistance(
			$from->system->xPosition,
			$to->system->xPosition,
			$from->system->xPosition,
			$to->system->xPosition,
		);
	}

	private function getDistance(int $xa, int $xb, int $ya, int $yb): int
	{
		$distance = intval(floor(sqrt((($xa - $xb) * ($xa - $xb)) + (($ya - $yb) * ($ya - $yb)))));

		return max(1, $distance);
	}
}
