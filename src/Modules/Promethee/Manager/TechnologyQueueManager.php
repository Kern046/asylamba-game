<?php

namespace App\Modules\Promethee\Manager;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Promethee\Domain\Event\NewTechnologyQueueEvent;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Message\TechnologyQueueMessage;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\SchedulerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class TechnologyQueueManager implements SchedulerInterface
{
	public function __construct(
		protected EventDispatcherInterface $eventDispatcher,
		private readonly TechnologyQueueRepositoryInterface $technologyQueueRepository,
		protected MessageBusInterface $messageBus
	) {
	}

	public function schedule(): void
	{
		$queues = $this->technologyQueueRepository->getAll();

		foreach ($queues as $queue) {
			$this->messageBus->dispatch(
				new TechnologyQueueMessage($queue->id),
				[DateTimeConverter::to_delay_stamp($queue->getEndDate())],
			);
		}
	}

	public function add(TechnologyQueue $technologyQueue, Player $player): void
	{
		$this->technologyQueueRepository->save($technologyQueue);

		$this->messageBus->dispatch(
			new TechnologyQueueMessage($technologyQueue->id),
			[DateTimeConverter::to_delay_stamp($technologyQueue->getEndDate())]
		);

		$this->eventDispatcher->dispatch(new NewTechnologyQueueEvent($technologyQueue, $player));
	}
}
