<?php

declare(strict_types=1);

namespace App\Modules\Promethee\Application\Factory;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Promethee\Domain\Event\NewTechnologyQueueEvent;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Domain\Service\GetTimeCost;
use App\Modules\Promethee\Message\TechnologyQueueMessage;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Shared\Application\Handler\DurationHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class TechnologyQueueFactory
{
	public function __construct(
		private DurationHandler $durationHandler,
		private EventDispatcherInterface $eventDispatcher,
		private GetTimeCost $getTimeCost,
		private MessageBusInterface $messageBus,
		private TechnologyQueueRepositoryInterface $technologyQueueRepository,
	) {
	}

	public function create(
		OrbitalBase        $orbitalBase,
		int                $identifier,
		int                $targetLevel,
		\DateTimeImmutable $createdAt,
	): TechnologyQueue {
		$technologyQueue = new TechnologyQueue(
			id: Uuid::v4(),
			player: $orbitalBase->player,
			place: $orbitalBase->place,
			technology: $identifier,
			targetLevel: $targetLevel,
			startedAt: $createdAt,
			endedAt: $this->durationHandler->getDurationEnd($createdAt, ($this->getTimeCost)(
				$identifier,
				$targetLevel,
				$orbitalBase->place->coefHistory,
				$orbitalBase->player,
			)),
		);

		$this->technologyQueueRepository->save($technologyQueue);

		$this->messageBus->dispatch(
			new TechnologyQueueMessage($technologyQueue->id),
			[DateTimeConverter::to_delay_stamp($technologyQueue->getEndDate())]
		);

		$this->eventDispatcher->dispatch(new NewTechnologyQueueEvent($technologyQueue));

		return $technologyQueue;
	}
}
