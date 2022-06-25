<?php

namespace App\Shared\Infrastructure\EventListener;

use App\Modules\Ares\Domain\Event\Commander\NewCommanderEvent;
use App\Modules\Ares\Domain\Event\Fleet\LootEvent;
use App\Modules\Artemis\Domain\Event\SpyEvent;
use App\Modules\Athena\Domain\Event\NewBuildingQueueEvent;
use App\Modules\Athena\Domain\Event\NewShipQueueEvent;
use App\Modules\Promethee\Domain\Event\NewTechnologyQueueEvent;
use App\Modules\Zeus\Domain\Event\PlayerConnectionEvent;
use App\Shared\Domain\Event\TrackingEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class MixpanelListener
{
	public function __construct(private \Mixpanel $mixpanel, string $environment)
	{
		$this->mixpanel->register('environment', $environment);
	}

	#[AsEventListener(priority: 10)]
	public function onPlayerConnection(PlayerConnectionEvent $event): void
	{
		$player = $event->player;

		$this->mixpanel->people->set($player->id, [
			'faction_id' => $player->faction->id,
		]);
	}

	#[AsEventListener(NewBuildingQueueEvent::class)]
	#[AsEventListener(NewShipQueueEvent::class)]
	#[AsEventListener(NewTechnologyQueueEvent::class)]
	#[AsEventListener(NewCommanderEvent::class)]
	#[AsEventListener(LootEvent::class)]
	#[AsEventListener(PlayerConnectionEvent::class)]
	#[AsEventListener(SpyEvent::class)]
	public function onTrackingEvent(TrackingEvent $event): void
	{
		$this->mixpanel->identify($event->getTrackingPeopleId());

		$this->mixpanel->track($event->getTrackingEventName(), $event->getTrackingData());
	}
}
