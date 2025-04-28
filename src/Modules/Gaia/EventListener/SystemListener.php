<?php

namespace App\Modules\Gaia\EventListener;

use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Gaia\Event\PlaceOwnerChangeEvent;

readonly class SystemListener
{
	public function __construct(
		private ColorRepositoryInterface       $colorRepository,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private SystemRepositoryInterface      $systemRepository,
		private array                          $scores,
	) {
	}

	public function onPlaceOwnerChange(PlaceOwnerChangeEvent $event): void
	{
		$scores = [];
		$system = $event->getPlace()->system;
		$bases = $this->orbitalBaseRepository->getSystemBases($system);

		foreach ($bases as $base) {
			$factionIdentifier = $base->player?->faction?->identifier;

			if (null === $factionIdentifier) {
				continue;
			}

			$scores[$factionIdentifier] = ($scores[$factionIdentifier] ?? 0) + $this->scores[$base->typeOfBase];
		}
		arsort($scores);
		$newColor = array_key_first($scores);
		$currentFactionIdentifier = $system->faction?->identifier;

		if (null === $currentFactionIdentifier || (
			$scores[$newColor] > 0
			&& $currentFactionIdentifier !== $newColor
			&& $scores[$newColor] > $scores[$currentFactionIdentifier]
		)) {
			$system->faction = $this->colorRepository->getOneByIdentifier($newColor);
		} elseif (0 === $scores[$newColor]) {
			$system->faction = null;
		}
		$this->systemRepository->save($system);
	}
}
