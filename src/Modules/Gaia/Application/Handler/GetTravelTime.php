<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Application\Handler;

use App\Classes\Library\Game;
use App\Modules\Ares\Application\Handler\GetFleetSpeed;
use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Domain\Model\TravelType;
use App\Modules\Gaia\Model\Place;
use App\Modules\Zeus\Model\PlayerBonus;

readonly class GetTravelTime
{
	public function __construct(
		private GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		private GetFleetSpeed $getFleetSpeed,
	) {
	}

	public function __invoke(
		Place $from,
		Place $to,
		TravelType $travelType = TravelType::Fleet,
		PlayerBonus|null $playerBonus = null,
	): int {
		$time = $this->calculateTravelTime($from, $to, $playerBonus);

		if ($travelType === TravelType::CommercialShipping) {
			$time = intval(round($time * Game::COMMERCIAL_TIME_TRAVEL));
		}

		return $time;
	}

	private function calculateTravelTime(Place $from, Place $to, PlayerBonus|null $playerBonus): int
	{
		return $from->system->id === $to->system->id
			? $this->getTimeTravelInSystem($from, $to)
			: $this->getTimeTravelOutOfSystem($from, $to, $playerBonus);
	}

	private function getTimeTravelInSystem(Place $from, Place $to): int
	{
		$distance = abs($from->position - $to->position);

		return intval(round((Commander::COEFFMOVEINSYSTEM * $distance) * ((40 - $distance) / 50) + 180));
	}

	private function getTimeTravelOutOfSystem(Place $from, Place $to, PlayerBonus|null $playerBonus): int
	{
		$distance = ($this->getDistanceBetweenPlaces)($from, $to);

		$time = Commander::COEFFMOVEOUTOFSYSTEM;
		$time += round((Commander::COEFFMOVEINTERSYSTEM * $distance) / ($this->getFleetSpeed)($playerBonus));

		return intval($time);
	}
}
