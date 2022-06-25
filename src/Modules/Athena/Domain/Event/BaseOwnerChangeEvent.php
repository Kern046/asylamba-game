<?php

namespace App\Modules\Athena\Domain\Event;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Zeus\Model\Player;

readonly class BaseOwnerChangeEvent
{
	public function __construct(
		private OrbitalBase $orbitalBase,
		private Player $previousOwner,
	) {

	}

	public function getOrbitalBase(): OrbitalBase
	{
		return $this->orbitalBase;
	}

	public function getPreviousOwner(): Player
	{
		return $this->previousOwner;
	}
}
