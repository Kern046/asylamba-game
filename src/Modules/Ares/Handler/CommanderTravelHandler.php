<?php

namespace App\Modules\Ares\Handler;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Manager\ConquestManager;
use App\Modules\Ares\Manager\LootManager;
use App\Modules\Ares\Message\CommanderTravelMessage;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Model\Place;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CommanderTravelHandler
{
	public function __construct(
		private CommanderManager             $commanderManager,
		private CommanderRepositoryInterface $commanderRepository,
		private ConquestManager              $conquestManager,
		private LootManager                  $lootManager,
		private PlaceManager                 $placeManager,
		private OrbitalBaseManager           $orbitalBaseManager,
		private EntityManagerInterface       $entityManager,
	) {
	}

	public function __invoke(CommanderTravelMessage $commanderTravelMessage): void
	{
		if (null === ($commander = $this->commanderRepository->get($commanderTravelMessage->getCommanderId()))) {
			return;
		}

		match ($commander->travelType) {
			Commander::MOVE => $this->commanderManager->uChangeBase($commander),
			Commander::LOOT => $this->lootManager->loot($commander),
			Commander::COLO => $this->conquestManager->conquer($commander),
			Commander::BACK => $this->moveBack($commander),
		};
	}

	protected function moveBack(Commander $commander): void
	{
		$this->placeManager->sendNotif($commander->destinationPlace, Place::COMEBACK, $commander);

		$this->commanderManager->endTravel($commander, Commander::AFFECTED);

		if ($commander->resources > 0) {
			$this->orbitalBaseManager->increaseResources($commander->base, $commander->resources, true);
			$commander->resources = 0;
		}
		$this->entityManager->flush($commander);
	}
}
