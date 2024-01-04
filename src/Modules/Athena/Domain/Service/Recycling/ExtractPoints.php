<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Recycling;

use App\Modules\Athena\Model\RecyclingMission;

/**
 * If the target Place has less resources than the maximum capacity of the mission ships,
 * The extracted points will equal the left resources.
 */
class ExtractPoints
{
	public function __invoke(RecyclingMission $recyclingMission): int
	{
		return min(
			$recyclingMission->target->resources,
			$recyclingMission->recyclerQuantity * RecyclingMission::RECYCLER_CAPACTIY,
		);
	}
}
