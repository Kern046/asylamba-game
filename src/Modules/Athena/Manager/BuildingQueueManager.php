<?php

namespace App\Modules\Athena\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Event\NewBuildingQueueEvent;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Message\Building\BuildingQueueMessage;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\SchedulerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class BuildingQueueManager implements SchedulerInterface
{
	public function __construct(
		private readonly MessageBusInterface $messenger,
		private readonly BuildingQueueRepositoryInterface $buildingQueueRepository,
		private readonly EventDispatcherInterface $eventDispatcher,
	) {
	}

	public function schedule(): void
	{
		$buildingQueues = $this->buildingQueueRepository->getAll();

		foreach ($buildingQueues as $buildingQueue) {
			$this->messenger->dispatch(new BuildingQueueMessage($buildingQueue->id), [DateTimeConverter::to_delay_stamp($buildingQueue->getEndDate())]);
		}
	}

	public function add(BuildingQueue $buildingQueue): void
	{
		$this->buildingQueueRepository->save($buildingQueue);

		$this->messenger->dispatch(
			new BuildingQueueMessage($buildingQueue->id),
			[DateTimeConverter::to_delay_stamp($buildingQueue->getEndDate()),
		]);

		$this->eventDispatcher->dispatch(new NewBuildingQueueEvent($buildingQueue));
	}
}
