<?php

namespace App\Modules\Gaia\Handler;

use App\Classes\Library\Utils;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Message\NpcsPlacesUpdateMessage;
use App\Modules\Gaia\Model\Place;
use App\Shared\Application\Handler\DurationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NpcsPlacesUpdateHandler
{
	public function __construct(
		private readonly EntityManagerInterface $entityManager,
		private readonly PlaceRepositoryInterface $placeRepository,
		private readonly DurationHandler $durationHandler,
	) {
	}

	public function __invoke(NpcsPlacesUpdateMessage $message): void
	{
		$placesCount = $this->placeRepository->countNpsPlaces();
		$limit = 20;

		for ($i = 0; $i < $placesCount; $i++) {
			$places = $this->placeRepository->getNpcPlaces($i, $limit);
			$this->entityManager->beginTransaction();

			foreach ($places as $place) {
				$this->updatePlace($place);
			}

			$this->entityManager->commit();
			$this->entityManager->clear();

			$i += $limit;
		}

		$this->placeRepository->npcQuickfix();
	}

	private function updatePlace(Place $place): void
	{
		$now = new \DateTimeImmutable();
		$hoursDiff = $this->durationHandler->getHoursDiff($place->updatedAt, $now);
		if (0 === $hoursDiff) {
			return;
		}
		// update time
		$place->updatedAt = new \DateTimeImmutable();
		$initialResources = $place->resources;
		$initialDanger = $place->danger;
		$maxResources = ceil($place->population / Place::COEFFPOPRESOURCE) * Place::COEFFMAXRESOURCE * ($place->maxDanger + 1);

		for ($i = 0; $i < $hoursDiff; ++$i) {
			$place->danger += Place::REPOPDANGER;
			$place->resources += floor(Place::COEFFRESOURCE * $place->population);
		}
		// The repository method will add the new resources. We have to calculate how many resources have been added
		$place->resources = abs($place->resources - $initialResources);
		// If the max is reached, we have to add just the difference between the max and init value
		if ($place->resources > $maxResources) {
			$place->resources = $maxResources - $initialResources;
		}
		$place->danger = abs($place->danger - $initialDanger);
		// Same thing here
		if ($place->danger > $place->maxDanger) {
			$place->danger = $place->maxDanger - $initialDanger;
		}
		$this->placeRepository->save($place);
	}
}
