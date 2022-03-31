<?php

/**
 * Building Queue Manager
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @package Athena
 * @update 10.02.14
*/
namespace App\Modules\Athena\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Event\NewBuildingQueueEvent;
use App\Modules\Athena\Message\Building\BuildingQueueMessage;
use App\Modules\Athena\Model\BuildingQueue;
use App\Classes\Entity\EntityManager;

use App\Modules\Zeus\Model\Player;
use App\Shared\Application\SchedulerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class BuildingQueueManager implements SchedulerInterface
{
	public function __construct(
		private MessageBusInterface     $messenger,
		private EntityManager           $entityManager,
		private EventDispatcherInterface $eventDispatcher,
	) {
	}
	
	public function get(int $id): ?BuildingQueue
	{
		return $this->entityManager->getRepository(BuildingQueue::class)->get($id);
	}
	
	public function getBaseQueues(int $baseId): array
	{
		return $this->entityManager->getRepository(BuildingQueue::class)->getBaseQueues($baseId);
	}
	
	public function schedule(): void
	{
		$buildingQueues = $this->entityManager->getRepository(BuildingQueue::class)->getAll();

		/** @var BuildingQueue $buildingQueue */
		foreach ($buildingQueues as $buildingQueue) {
			$this->messenger->dispatch(new BuildingQueueMessage($buildingQueue->id), [DateTimeConverter::to_delay_stamp($buildingQueue->getDEnd())]);
		}
	}

	public function add(BuildingQueue $buildingQueue, Player $player): void
	{
		$this->entityManager->persist($buildingQueue);
		$this->entityManager->flush($buildingQueue);

		$this->messenger->dispatch(new BuildingQueueMessage($buildingQueue->id), [DateTimeConverter::to_delay_stamp($buildingQueue->getDEnd())]);

		$this->eventDispatcher->dispatch(new NewBuildingQueueEvent($buildingQueue, $player));
	}
}
