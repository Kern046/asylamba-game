<?php

namespace App\Modules\Artemis\Domain\Event;

use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TrackingEvent;
use App\Shared\Domain\Event\TutorialEvent;

class SpyEvent implements TutorialEvent, TrackingEvent
{
    public function __construct(
        public readonly SpyReport $spyReport,
        public readonly Player $player
    ) {
    }

    public function getTutorialPlayer(): Player
    {
        return $this->player;
    }

    public function getTutorialStep(): int|null
    {
        return TutorialResource::SPY_PLANET;
    }

    public function getTrackingPeopleId(): int
    {
        return $this->player->id;
    }

    public function getTrackingEventName(): string
    {
        return 'New Spy Report';
    }

    public function getTrackingData(): array
    {
        return [
            'place_id' => $this->spyReport->rPlace,
            'enemy_id' => $this->spyReport->rEnemy,
            'price' => $this->spyReport->price,
            'success_percent' => $this->spyReport->success,
        ];
    }
}
