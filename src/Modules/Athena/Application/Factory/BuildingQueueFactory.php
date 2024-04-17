<?php

declare(strict_types=1);

namespace App\Modules\Athena\Application\Factory;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Event\NewBuildingQueueEvent;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Message\Building\BuildingQueueMessage;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Shared\Application\Server\TimeModeApplier;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Shared\Application\Handler\DurationHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class BuildingQueueFactory
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		private BuildingQueueRepositoryInterface $buildingQueueRepository,
		private DurationHandler $durationHandler,
		private EventDispatcherInterface $eventDispatcher,
		private MessageBusInterface $messageBus,
		private OrbitalBaseHelper $orbitalBaseHelper,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function create(
		OrbitalBase 	   $orbitalBase,
		int                $identifier,
		int                $targetLevel,
		\DateTimeImmutable $startedAt,
	): BuildingQueue {
		// build the new building
		$buildingQueue = new BuildingQueue(
			id: Uuid::v4(),
			base: $orbitalBase,
			buildingNumber: $identifier,
			targetLevel: $targetLevel,
			startedAt: $startedAt,
			endedAt: $this->durationHandler->getDurationEnd($startedAt, $this->getDurationEnding($identifier, $targetLevel)),
		);

		$this->buildingQueueRepository->save($buildingQueue);

		$this->messageBus->dispatch(
			new BuildingQueueMessage($buildingQueue->id),
			[DateTimeConverter::to_delay_stamp($buildingQueue->getEndDate())],
		);

		$this->eventDispatcher->dispatch(new NewBuildingQueueEvent($buildingQueue));

		return $buildingQueue;
	}

	private function getDurationEnding(int $identifier, int $targetLevel): int
	{
		$time = $this->timeMode->isStandard()
			? $this->orbitalBaseHelper->getBuildingInfo($identifier, 'level', $targetLevel, 'time')
			: $targetLevel * 10;

		$bonus = $this->bonusApplier->apply($time, PlayerBonusId::GENERATOR_SPEED);

		return intval(round($time - $bonus));
	}
}
