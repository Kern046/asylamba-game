<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Handler;

use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Message\PlaceUpdateMessage;
use App\Modules\Gaia\Model\Place;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class PlaceUpdateHandler
{
	public function __construct(
		private PlaceRepositoryInterface $placeRepository,
		private DurationHandler $durationHandler,
	) {
	}

	public function __invoke(PlaceUpdateMessage $message): void
	{
		$place = $this->placeRepository->get($message->placeId) ?? throw new \RuntimeException(sprintf('Place %s not found', $message->placeId));

		$now = new \DateTimeImmutable();
		$hoursDiff = $this->durationHandler->getHoursDiff($place->updatedAt, $now);
		if (0 === $hoursDiff) {
			return;
		}
		// update time
		$place->updatedAt = $now;
		$initialResources = $place->resources;
		$maxResources = $this->getMaxResources($place);

		$place->resources += $this->getProducedResources($place) * $hoursDiff;
		$place->resources = abs($place->resources - $initialResources);
		if ($place->resources > $maxResources) {
			$place->resources = $maxResources;
		}

		if (null === $place->player) {
			$this->updateNpcPlace($place, $hoursDiff);
		}

		$this->placeRepository->save($place);
	}

	private function updateNpcPlace(Place $place, int $hoursDiff): void
	{
		$initialDanger = $place->danger;

		$place->danger += Place::REPOPDANGER * $hoursDiff;
		$place->danger = abs($place->danger - $initialDanger);
		// Same thing here
		if ($place->danger > $place->maxDanger) {
			$place->danger = $place->maxDanger - $initialDanger;
		}
	}

	private function getMaxResources(Place $place): int
	{
		return intval(
			ceil($place->population / Place::COEFFPOPRESOURCE)
			* Place::COEFFMAXRESOURCE
			* ($place->maxDanger + 1)
		);
	}

	private function getProducedResources(Place $place): int
	{
		return intval(floor(Place::COEFFRESOURCE * $place->population));
	}
}
