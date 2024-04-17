<?php

declare(strict_types=1);

namespace App\Modules\Travel\Domain\Service;

use App\Classes\Library\Game;
use App\Modules\Ares\Application\Handler\GetFleetSpeed;
use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Gaia\Model\Place;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class GetTravelDuration
{
	public function __construct(
		private DurationHandler $durationHandler,
		private GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		private GetFleetSpeed $getFleetSpeed,
		private PlayerBonusManager $playerBonusManager,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function __invoke(
		Place              $origin,
		Place              $destination,
		\DateTimeImmutable $departureDate,
		TravelType         $travelType = TravelType::Fleet,
		Player|null        $player = null,
	): \DateTimeImmutable {
		$playerBonus = null !== $player ? $this->playerBonusManager->getBonusByPlayer($player) : null;
		$time = $this->calculateTravelTime($origin, $destination, $playerBonus);

		if ($travelType === TravelType::CommercialShipping) {
			$time = intval(round($time * Game::COMMERCIAL_TIME_TRAVEL));
		}

		if ($this->timeMode->isFast()) {
			$time = match ($travelType) {
				TravelType::Fleet => 300,
				TravelType::CommercialShipping => 120,
				TravelType::RecyclingShips => 1800,
			};
		}

		return $this->durationHandler->getDurationEnd($departureDate, $time);
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
