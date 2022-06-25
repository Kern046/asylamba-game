<?php

namespace App\Modules\Gaia\EventListener;

use App\Classes\Entity\EntityManager;
use App\Classes\Redis\RedisManager;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Gaia\Event\PlaceOwnerChangeEvent;
use App\Modules\Gaia\Manager\SectorManager;
use App\Modules\Gaia\Manager\SystemManager;

class SectorListener
{
	public function __construct(
		private readonly ColorRepositoryInterface $colorRepository,
		private readonly SectorManager $sectorManager,
		private readonly int $sectorMinimalScore,
	) {
	}

	public function onPlaceOwnerChange(PlaceOwnerChangeEvent $event): void
	{
		$system = $event->getPlace()->system;
		$sector = $system->sector;
		$scores = $this->sectorManager->calculateOwnership($sector);

		$newColor = key($scores);
		$hasEnoughPoints = false;
		foreach ($scores as $factionId => $score) {
			if (0 !== $factionId && $score >= $this->sectorMinimalScore) {
				$hasEnoughPoints = true;
				break;
			}
		}

		$currentFactionIdentifier = $sector->faction?->identifier;
		// If the faction has more points than the minimal score and the current owner of the sector, he claims it
		if (true === $hasEnoughPoints && null === $currentFactionIdentifier || ($currentFactionIdentifier !== $newColor && $scores[$newColor] > $scores[$currentFactionIdentifier])) {
			$sector->faction = $this->colorRepository->getOneByIdentifier($newColor);
		// If this is a prime sector, we do not pull back the color from the sector
		} elseif (false === $hasEnoughPoints && false === $sector->prime) {
			$sector->faction = null;
		}
	}
}
