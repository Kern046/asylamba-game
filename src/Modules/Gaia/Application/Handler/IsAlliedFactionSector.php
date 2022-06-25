<?php

namespace App\Modules\Gaia\Application\Handler;

use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Model\Sector;

class IsAlliedFactionSector
{
	public function __invoke(Sector $sector, Color $faction): bool
	{
		return $sector->faction?->id === $faction->id
			|| Color::ALLY === $sector->faction?->relations[$faction->identifier];
	}
}
