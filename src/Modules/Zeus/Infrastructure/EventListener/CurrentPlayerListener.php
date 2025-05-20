<?php

namespace App\Modules\Zeus\Infrastructure\EventListener;

use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener]
readonly class CurrentPlayerListener
{
	public function __construct(
		private PlayerRepositoryInterface $playerRepository,
		private PlayerBonusManager $playerBonusManager,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private CurrentPlayerRegistry $currentPlayerRegistry,
		private CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		private CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
	) {
	}

	public function __invoke(RequestEvent $event): void
	{
		$request = $event->getRequest();

		if (!$request->hasPreviousSession() || null === ($playerId = $request->getSession()->get('playerId'))) {
			return;
		}

		$player = $this->playerRepository->get($playerId);
		$this->currentPlayerRegistry->set($player);
		$this->currentPlayerBasesRegistry->setBases($this->orbitalBaseRepository->getPlayerBases($player));
		$this->currentPlayerBasesRegistry->setCurrentBase($request->getSession()->get('playerParams')->get('base'));

		$bonus = $this->playerBonusManager->getBonusByPlayer($player);
		$this->currentPlayerBonusRegistry->setPlayerBonus($bonus);
	}
}
