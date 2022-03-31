<?php

namespace App\Shared\Infrastructure\EventSubscriber;

use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
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
				['setCurrentPlayerBases'],
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
		$currentBase = $this->orbitalBaseManager->get($this->session->get('playerParams')->get('base'));
		$this->twig->addGlobal('current_base', $currentBase);
		$this->twig->addGlobal('incoming_commanders', $this->commanderManager->getVisibleIncomingAttacks($playerId));
		$this->twig->addGlobal('outgoing_commanders', $this->commanderManager->getPlayerCommanders($playerId, [Commander::MOVING]));
		$this->twig->addGlobal('current_dock1_ship_queues',  $this->shipQueueManager->getByBaseAndDockType($currentBase->rPlace, 1));
		$this->twig->addGlobal('current_dock2_ship_queues',  $this->shipQueueManager->getByBaseAndDockType($currentBase->rPlace, 2));
	}
	
	public function setCurrentPlayerBases(): void
	{
		if (null === ($playerParams = $this->session->get('playerParams'))) {
			return;
		}
		$currentBaseName = NULL;
		$currentBaseImg  = NULL;
		$ob = $this->session->get('playerBase')->get('ob');
		for ($i = 0; $i < $ob->size(); $i++) {
			if ($playerParams->get('base') == $ob->get($i)->get('id')) {
				$currentBaseName = $ob->get($i)->get('name');
				$currentBaseImg  = $ob->get($i)->get('img');
				break;
			}
		}

		if ($ob->get(0)) {
			$nextBaseId = $ob->get(0)->get('id');
			$isFound = false;
			for ($i = 0; $i < $ob->size(); $i++) {
				if ($isFound) {
					$nextBaseId = $ob->get($i)->get('id');
					break;
				}
				if ($playerParams->get('base') == $ob->get($i)->get('id')) {
					$isFound = true;
				}
			}
		} else {
			$nextBaseId = 0;
			$currentBaseName = 'Reconnectez-vous';
			$currentBaseImg = '1-1';
		}
		$this->twig->addGlobal('next_base_id', $nextBaseId);
		$this->twig->addGlobal('current_base_name', $currentBaseName);
		$this->twig->addGlobal('current_base_image', $currentBaseImg);
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
