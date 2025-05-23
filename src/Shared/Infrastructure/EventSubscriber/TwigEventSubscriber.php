<?php

namespace App\Shared\Infrastructure\EventSubscriber;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
	protected SessionInterface|null $session = null;

	public function __construct(
		private readonly Environment $twig,
		private readonly CommanderRepositoryInterface $commanderRepository,
		private readonly ConversationRepositoryInterface $conversationRepository,
		private readonly NotificationRepositoryInterface $notificationRepository,
		private readonly CurrentPlayerRegistry $currentPlayerRegistry,
		private readonly CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		private readonly ShipQueueRepositoryInterface $shipQueueRepository,
		private readonly BuildingQueueRepositoryInterface $buildingQueueRepository,
		private readonly TechnologyQueueRepositoryInterface $technologyQueueRepository,
	) {
	}

	public static function getSubscribedEvents(): array
	{
		return [
			ControllerEvent::class => [
				['setCurrentPlayer'],
				['setCurrentBase'],
			],
		];
	}

	public function setCurrentBase(): void
	{
		if (!$this->currentPlayerRegistry->has()) {
			return;
		}

		$player = $this->currentPlayerRegistry->get();
		$currentBase = $this->currentPlayerBasesRegistry->current();

		$this->twig->addGlobal('current_base', $currentBase);
		$this->twig->addGlobal('current_player_bases', $this->currentPlayerBasesRegistry->all());
		$this->twig->addGlobal('first_base', $this->currentPlayerBasesRegistry->first());
		$this->twig->addGlobal('next_base', $this->currentPlayerBasesRegistry->next());
		$this->twig->addGlobal('incoming_commanders', $this->commanderRepository->getIncomingAttacks($player));
		$this->twig->addGlobal('outgoing_commanders', $this->commanderRepository->getOutcomingAttacks($player));
		$this->twig->addGlobal('current_building_queues', $this->buildingQueueRepository->getBaseQueues($currentBase));
		$this->twig->addGlobal('current_technology_queues', $this->technologyQueueRepository->getPlaceQueues($currentBase->place));
		$this->twig->addGlobal('current_dock1_ship_queues', $this->shipQueueRepository->getByBaseAndDockType($currentBase, 1));
		$this->twig->addGlobal('current_dock2_ship_queues', $this->shipQueueRepository->getByBaseAndDockType($currentBase, 2));
	}

	public function setCurrentPlayer(): void
	{
		if (!$this->currentPlayerRegistry->has()) {
			return;
		}
		$currentPlayer = $this->currentPlayerRegistry->get();

		$this->twig->addGlobal('current_player', $currentPlayer);
		$this->twig->addGlobal('current_player_faction_id', $currentPlayer->faction->identifier);
		$this->twig->addGlobal('conversations_count', $this->conversationRepository->countPlayerUnreadConversations($currentPlayer));
		$this->twig->addGlobal('current_player_notifications', $this->notificationRepository->getUnreadNotifications($currentPlayer, 15));
		$this->twig->addGlobal('current_player_notifications_count', $this->notificationRepository->countUnreadNotifications($currentPlayer));
	}
}
