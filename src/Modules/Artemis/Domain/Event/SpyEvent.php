<?php

namespace App\Modules\Artemis\Domain\Event;

use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Shared\Domain\Event\TutorialEvent;

class SpyEvent implements TutorialEvent
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
}
