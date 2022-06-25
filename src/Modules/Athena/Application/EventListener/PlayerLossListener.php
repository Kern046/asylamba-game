<?php

namespace App\Modules\Athena\Application\EventListener;

use App\Modules\Athena\Domain\Event\BaseOwnerChangeEvent;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class PlayerLossListener
{
	public function __construct(
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private PlayerManager $playerManager,
	) {

	}

	public function __invoke(BaseOwnerChangeEvent $event): void
	{
		// vérifie si le joueur n'a plus de planète, si c'est le cas, il est mort, on lui redonne une planète
		$previousOwner = $event->getPreviousOwner();
		$base = $event->getOrbitalBase();

		$oldPlayerBases = $this->orbitalBaseRepository->getPlayerBases($previousOwner);
		$nbOldPlayerBases = count($oldPlayerBases);
		if (0 === $nbOldPlayerBases || (1 === $nbOldPlayerBases && $oldPlayerBases[0]->id === $base->id)) {
			$this->playerManager->reborn($previousOwner);
		}
	}
}
