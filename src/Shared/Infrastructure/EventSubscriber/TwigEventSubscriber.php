<?php

namespace App\Shared\Infrastructure\EventSubscriber;

use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\ShipQueueManager;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
	protected SessionInterface|null $session;
	
	public function __construct(
		protected Environment $twig,
		protected ConversationRepositoryInterface $conversationRepository,
		protected NotificationManager $notificationManager,
		protected CurrentPlayerRegistry $currentPlayerRegistry,
		private CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		protected CommanderManager $commanderManager,
		protected ShipQueueManager $shipQueueManager,
		protected OrbitalBaseManager $orbitalBaseManager,
		RequestStack $requestStack,
	) {
		if (null !== $requestStack->getMainRequest()) {
			$this->session = $requestStack->getSession();
		}
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
		if (null === ($playerId = $this->session->get('playerId'))) {
			return;
		}
		$currentBase = $this->currentPlayerBasesRegistry->current();
		$this->twig->addGlobal('current_base', $currentBase);
		$this->twig->addGlobal('next_base', $this->currentPlayerBasesRegistry->next());
		$this->twig->addGlobal('incoming_commanders', $this->commanderManager->getVisibleIncomingAttacks($playerId));
		$this->twig->addGlobal('outgoing_commanders', $this->commanderManager->getPlayerCommanders($playerId, [Commander::MOVING]));
		$this->twig->addGlobal('current_dock1_ship_queues',  $this->shipQueueManager->getByBaseAndDockType($currentBase->rPlace, 1));
		$this->twig->addGlobal('current_dock2_ship_queues',  $this->shipQueueManager->getByBaseAndDockType($currentBase->rPlace, 2));
	}

	public function setCurrentPlayer(): void
	{
		if (!$this->currentPlayerRegistry->has()) {
			return;
		}
		$currentPlayer = $this->currentPlayerRegistry->get();

		$this->twig->addGlobal('current_player', $currentPlayer);
		// @TODO handle registration to avoid accessing the session for this value
		$this->twig->addGlobal('current_player_faction_id', $this->session->get('playerInfo')->get('color'));
		$this->twig->addGlobal('conversations_count', $this->conversationRepository->countPlayerConversations($currentPlayer->getId()));
		$this->twig->addGlobal('current_player_notifications', $this->notificationManager->getUnreadNotifications($currentPlayer->getId()));
	}
}
