<?php

namespace App\Modules\Zeus\Domain\Event;

use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Event\TrackingEvent;

class PlayerConnectionEvent implements TrackingEvent
{
    public function __construct(
        public readonly Player $player,
    ) {
    }

    public function getTrackingPeopleId(): int
    {
        return $this->player->id;
    }

    public function getTrackingEventName(): string
    {
        return 'Player Connection';
    }

    public function getTrackingData(): array
    {
        return [];
    }
}
