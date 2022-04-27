<?php

/**
 * Ship Queue Manager.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 10.02.14
 */

namespace App\Modules\Athena\Manager;

use App\Classes\Entity\EntityManager;
use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Event\NewShipQueueEvent;
use App\Modules\Athena\Message\Ship\ShipQueueMessage;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\SchedulerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ShipQueueManager implements SchedulerInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        protected EntityManager $entityManager,
        protected MessageBusInterface $messageBus,
    ) {
    }

    public function get($id): ?ShipQueue
    {
        return $this->entityManager->getRepository(ShipQueue::class)->get($id);
    }

    public function getBaseQueues(int $orbitalBaseId): array
    {
        return $this->entityManager->getRepository(ShipQueue::class)->getBaseQueues($orbitalBaseId);
    }

    public function getByBaseAndDockType(int $orbitalBaseId, int $dockType): array
    {
        return $this->entityManager->getRepository(ShipQueue::class)->getByBaseAndDockType($orbitalBaseId, $dockType);
    }

    public function add(ShipQueue $shipQueue, Player $player): void
    {
        $this->entityManager->persist($shipQueue);
        $this->entityManager->flush($shipQueue);

        $this->messageBus->dispatch(new ShipQueueMessage($shipQueue->getId()), [DateTimeConverter::to_delay_stamp($shipQueue->dEnd)]);

        $this->eventDispatcher->dispatch(new NewShipQueueEvent($shipQueue, $player));
    }

    public function schedule(): void
    {
        $queues = $this->entityManager->getRepository(ShipQueue::class)->getAll();

        foreach ($queues as $queue) {
            $this->messageBus->dispatch(new ShipQueueMessage($queue->getId()), [DateTimeConverter::to_delay_stamp($queue->dEnd)]);
        }
    }
}
