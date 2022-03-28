<?php

namespace App\Modules\Zeus\Infrastructure\EventListener;

use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener]
class CurrentPlayerListener
{
	public function __construct(
		private PlayerManager $playerManager,
		private CurrentPlayerRegistry $currentPlayerRegistry
	) {

	}

	public function __invoke(RequestEvent $event): void
	{
		$request = $event->getRequest();

		if (null === ($playerId = $request->getSession()->get('playerId'))) {
			return;
		}

		$this->currentPlayerRegistry->set($this->playerManager->get($playerId));
	}
}
