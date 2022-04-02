<?php

namespace App\Modules\Athena\Domain\Event;

use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

class NewShipQueueEvent implements TutorialEvent, TrackingEvent
{
	public function __construct(
		public readonly ShipQueue $shipQueue,
		public readonly Player $player,
	) {

	}

	public function getTutorialPlayer(): Player
	{
		return $this->player;
	}

	public function getTutorialStep(): int|null
	{
		return match ($this->shipQueue->shipNumber) {
			ShipResource::PEGASE => TutorialResource::BUILD_SHIP0,
			ShipResource::SATYRE => TutorialResource::BUILD_SHIP1,
			default => null,
		};
	}

	public function getTrackingPeopleId(): int
	{
		return $this->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Ships Ordered';
	}

	public function getTrackingData(): array
	{
		return [
			'ship_id' => $this->shipQueue->shipNumber,
			'quantity' => $this->shipQueue->quantity,
			'place_id' => $this->shipQueue->rOrbitalBase,
		];
	}
}
