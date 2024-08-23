<?php

namespace App\Modules\Athena\Domain\Event;

use App\Modules\Athena\Domain\Model\ShipType;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

readonly class NewShipQueueEvent implements TutorialEvent, TrackingEvent
{
	public function __construct(
		public ShipQueue $shipQueue,
	) {
	}

	public function getTutorialPlayer(): Player
	{
		return $this->shipQueue->base->player;
	}

	public function getTutorialStep(): int|null
	{
		return match ($this->shipQueue->shipType) {
			ShipType::Pegase => TutorialResource::BUILD_SHIP0,
			ShipType::Satyre => TutorialResource::BUILD_SHIP1,
			default => null,
		};
	}

	public function getTrackingPeopleId(): int
	{
		return $this->shipQueue->base->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Ships Ordered';
	}

	public function getTrackingData(): array
	{
		return [
			'ship_name' => $this->shipQueue->shipType->value,
			'quantity' => $this->shipQueue->quantity,
			'place_id' => $this->shipQueue->base->id,
		];
	}
}
