<?php

namespace App\Modules\Ares\Domain\Event\Fleet;

use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Model\Place;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TutorialEvent;

class LootEvent implements TutorialEvent
{
	public function __construct(
		public readonly Place $place,
		public readonly Commander $commander,
		public readonly Player $attacker,
	) {

	}

	public function getTutorialPlayer(): Player
	{
		return $this->attacker;
	}

	public function getTutorialStep(): int
	{
		return TutorialResource::LOOT_PLANET;
	}
}
