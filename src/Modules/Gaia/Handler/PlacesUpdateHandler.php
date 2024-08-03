<?php

namespace App\Modules\Gaia\Handler;

use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Message\PlaceUpdateMessage;
use App\Modules\Gaia\Message\PlacesUpdateMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class PlacesUpdateHandler
{
	public function __construct(
		private PlaceRepositoryInterface $placeRepository,
		private MessageBusInterface      $messageBus,
	) {
	}

	public function __invoke(PlacesUpdateMessage $message): void
	{
		foreach ($this->placeRepository->getAll() as $place) {
			$this->messageBus->dispatch(new PlaceUpdateMessage($place->id));
		}
	}
}
