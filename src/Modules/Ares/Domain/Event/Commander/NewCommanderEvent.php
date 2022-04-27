<?php

namespace App\Modules\Ares\Domain\Event\Commander;

use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

class NewCommanderEvent implements TutorialEvent, TrackingEvent
{
    public function __construct(
        public readonly Commander $commander,
        public readonly Player $player,
    ) {
    }

    public function getTutorialPlayer(): Player
    {
        return $this->player;
    }

    public function getTutorialStep(): int|null
    {
        return TutorialResource::CREATE_COMMANDER;
    }

    public function getTrackingPeopleId(): int
    {
        return $this->commander->rPlayer;
    }

    public function getTrackingEventName(): string
    {
        return 'Commander Recruited';
    }

    public function getTrackingData(): array
    {
        return [
            'commander_id' => $this->commander->id,
            'place_id' => $this->commander->rBase,
        ];
    }
}
