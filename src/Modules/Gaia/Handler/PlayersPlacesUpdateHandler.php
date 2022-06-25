<?php

namespace App\Modules\Gaia\Handler;

use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Message\PlayersPlacesUpdateMessage;
use App\Modules\Gaia\Model\Place;
use App\Shared\Application\Handler\DurationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PlayersPlacesUpdateHandler
{
	public function __construct(
		private readonly EntityManagerInterface $entityManager,
		private readonly PlaceRepositoryInterface $placeRepository,
		private readonly DurationHandler $durationHandler,
	) {
	}

	public function __invoke(PlayersPlacesUpdateMessage $message): void
	{
		$placesCount = $this->placeRepository->countPlayerPlaces();
		$limit = 20;

		for ($i = 0; $i < $placesCount; $i++) {
			$places = $this->placeRepository->getPlayerPlaces($i, $limit);
			$this->entityManager->beginTransaction();

			foreach ($places as $place) {
				$this->updatePlace($place);
			}

			$this->entityManager->commit();
			$this->entityManager->clear();

			$i += $limit;
		}
	}

	private function updatePlace(Place $place): void
	{
		$now = new \DateTimeImmutable();
		$hoursDiff = $this->durationHandler->getHoursDiff($place->updatedAt, $now);
		if (0 === $hoursDiff) {
			return;
		}
		// update time
		$place->updatedAt = $now;
		$initialResources = $place->resources;
		// TODO factorize this calculation
		$maxResources = ceil($place->population / Place::COEFFPOPRESOURCE)
			* Place::COEFFMAXRESOURCE
			* ($place->maxDanger + 1);
		for ($i = 0; $i < $hoursDiff; $i++) {
			$place->resources += floor(Place::COEFFRESOURCE * $place->population);
		}
		$place->resources = abs($place->resources - $initialResources);
		if ($place->resources > $maxResources) {
			$place->resources = $maxResources;
		}
		$this->placeRepository->save($place);
	}
}
