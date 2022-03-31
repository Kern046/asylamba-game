<?php

namespace App\Modules\Athena\Domain\Event;

use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TutorialEvent;

class NewShipQueueEvent implements TutorialEvent
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
}
