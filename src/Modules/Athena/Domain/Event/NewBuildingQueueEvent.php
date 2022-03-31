<?php

namespace App\Modules\Athena\Domain\Event;

use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Zeus\Model\Player;

class NewBuildingQueueEvent
{
	public function __construct(
		public readonly BuildingQueue $buildingQueue,
		public readonly Player $player,
	) {

	}
}
