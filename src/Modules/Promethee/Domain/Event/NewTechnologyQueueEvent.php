<?php

namespace App\Modules\Promethee\Domain\Event;

use App\Modules\Promethee\Model\Technology;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

class NewTechnologyQueueEvent implements TutorialEvent, TrackingEvent
{
	public function __construct(
		public readonly TechnologyQueue $technologyQueue,
		public readonly Player $player,
	) {

	}

	public function getTutorialPlayer(): Player
	{
		return $this->player;
	}

	public function getTutorialStep(): int|null
	{
		return match ($this->technologyQueue->technology) {
			Technology::SHIP0_UNBLOCK => TutorialResource::SHIP0_UNBLOCK,
			Technology::SHIP1_UNBLOCK => TutorialResource::SHIP1_UNBLOCK,
			default => null,
		};
	}

	public function getTrackingPeopleId(): int
	{
		return $this->player->id;
	}

	public function getTrackingEventName(): string
	{
		return 'Technology Search Beginning';
	}

	public function getTrackingData(): array
	{
		return [
			'technology_id' => $this->technologyQueue->technology,
			'place_id' => $this->technologyQueue->rPlace,
			'target_level' => $this->technologyQueue->targetLevel,
		];
	}
}
