<?php

declare(strict_types=1);

namespace App\Modules\Gaia\EventListener;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Event\PlaceOwnerChangeEvent;
use App\Modules\Gaia\Manager\SectorManager;

readonly class SectorListener
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private SectorManager $sectorManager,
		private SectorRepositoryInterface $sectorRepository,
		private int $sectorMinimalScore,
	) {
	}

	public function onPlaceOwnerChange(PlaceOwnerChangeEvent $event): void
	{
		$system = $event->getPlace()->system;
		$sector = $system->sector;
		$scores = $this->sectorManager->calculateOwnership($sector);

		$newColor = key($scores);
		$score = $scores[$newColor];
		$hasEnoughPoints = $score >= $this->sectorMinimalScore;

		$currentFactionIdentifier = $sector->faction?->identifier ?? 0;

		if (!$hasEnoughPoints) {
			// If this is a prime sector, we do not pull back the color from the sector
			// TODO check behavior if another faction has taken the prime sector before
			if (!$sector->prime) {
				$sector->faction = null;
			}
		} elseif ($currentFactionIdentifier !== $newColor && $score > $scores[$currentFactionIdentifier]) {
			$sector->faction = $this->colorRepository->getOneByIdentifier($newColor);
		}

		$this->sectorRepository->save($sector);
	}
}
