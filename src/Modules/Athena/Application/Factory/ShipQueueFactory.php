<?php

declare(strict_types=1);

namespace App\Modules\Athena\Application\Factory;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Event\NewShipQueueEvent;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Message\Ship\ShipQueueMessage;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Shared\Application\Handler\DurationHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class ShipQueueFactory
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		private DurationHandler $durationHandler,
		private EventDispatcherInterface $eventDispatcher,
		private MessageBusInterface $messageBus,
		private ShipQueueRepositoryInterface $shipQueueRepository,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function create(
		OrbitalBase $orbitalBase,
		int $shipIdentifier,
		int $dockType,
		int $quantity,
		\DateTimeImmutable $startedAt,
	): ShipQueue {

		$shipQueue = new ShipQueue(
			id: Uuid::v4(),
			base: $orbitalBase,
			startedAt: $startedAt,
			endedAt: $this->durationHandler->getDurationEnd($startedAt, $this->getDurationEnding(
				shipIdentifier: $shipIdentifier,
				dockType: $dockType,
				quantity: $quantity,
			)),
			dockType: $dockType,
			shipNumber: $shipIdentifier,
			quantity: $quantity,
		);

		$this->shipQueueRepository->save($shipQueue);

		$this->messageBus->dispatch(
			new ShipQueueMessage($shipQueue->id),
			[DateTimeConverter::to_delay_stamp($shipQueue->getEndDate())],
		);

		$this->eventDispatcher->dispatch(new NewShipQueueEvent($shipQueue));

		return $shipQueue;
	}

	private function getDurationEnding(int $shipIdentifier, int $dockType, int $quantity): int
	{
		$time = $this->timeMode->isStandard()
			? ShipResource::getInfo($shipIdentifier, 'time') * $quantity
			: min($shipIdentifier * $quantity, 5);

		$bonus = $this->bonusApplier->apply($time, match ($dockType) {
			1 => PlayerBonusId::DOCK1_SPEED,
			2 => PlayerBonusId::DOCK2_SPEED,
			3 => PlayerBonusId::DOCK3_SPEED,
			default => throw new \LogicException('Invalid Dock ID'),
		});

		return intval(round($time - $bonus));
	}
}
