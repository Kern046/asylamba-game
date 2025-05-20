<?php

namespace App\Modules\Promethee\Handler;

use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Message\TechnologyQueueMessage;
use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TechnologyQueueHandler
{
	public function __construct(
		private readonly TechnologyQueueRepositoryInterface $technologyQueueRepository,
		private readonly PlayerManager $playerManager,
		private readonly TechnologyRepositoryInterface $technologyRepository,
		private readonly TechnologyHelper $technologyHelper,
	) {
	}

	public function __invoke(TechnologyQueueMessage $message): void
	{
		if (null === ($technologyQueue = $this->technologyQueueRepository->get($message->getTechnologyQueueId()))) {
			return;
		}
		$player = $technologyQueue->player;

		$technology = $this->technologyRepository->getPlayerTechnology($player);

		$technology->setTechnology($technologyQueue->technology, $technologyQueue->targetLevel);
		$experience = $this->technologyHelper->getInfo($technologyQueue->technology, 'points', $technologyQueue->targetLevel);
		$this->playerManager->increaseExperience($player, $experience);

		// alert @TODO replace with Mercure
		// $orbitalBase = $this->orbitalBaseManager->get($technologyQueue->getPlaceId());
		//		if (($session = $this->clientManager->getSessionByPlayerId($player->getId())) !== null) {
		//			$alt = 'Développement de votre technologie ' . $this->technologyHelper->getInfo($technologyQueue->getTechnology(), 'name');
		//			if ($technologyQueue->getTargetLevel() > 1) {
		//				$alt .= ' niveau ' . $technologyQueue->getTargetLevel();
		//			}
		//			$alt .= ' terminée. Vous gagnez ' . $experience . ' d\'expérience.';
		//			$session->addFlashbag($alt, Flashbag::TYPE_TECHNOLOGY_SUCCESS);
		//			$this->sessionWrapper->save($session);
		//		}
		$this->technologyQueueRepository->remove($technologyQueue);
		$this->technologyRepository->save($technology);
	}
}
