<?php

namespace App\Modules\Zeus\Helper;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;

readonly class TutorialHelper
{
	public function __construct(
		private EntityManagerInterface             $entityManager,
		private OrbitalBaseRepositoryInterface     $orbitalBaseRepository,
		private BuildingQueueRepositoryInterface   $buildingQueueRepository,
		private TechnologyRepositoryInterface      $technologyRepository,
		private TechnologyQueueRepositoryInterface $technologyQueueRepository,
		private BuildingLevelHandler               $buildingLevelHandler,
	) {
	}

	/*public function checkTutorial(): void
	{
		// PAS UTILISEE POUR L'INSTANT (le sera quand il y aura une étape passive dans le tutoriel)
		$player = $this->sessionWrapper->get('playerId');
		$stepTutorial = $this->sessionWrapper->get('playerInfo')->get('stepTutorial');
		$stepDone = $this->sessionWrapper->get('playerInfo')->get('stepDone');

		if ($stepTutorial > 0) {
			if (false == $stepDone) {
				// check if current step is done

				// hint : checker seulement les actions passives
				switch ($stepTutorial) {
					case 1:
						$asdf = 'asdf';
						break;
					case 2:
						$jlk = 'jkl';
						break;
				}
			}
		}
	}*/

	public function setStepDone(Player $player): void
	{
		$player->stepDone = true;

		$this->entityManager->flush($player);
	}

	public function clearStepDone(Player $player): void
	{
		$player->stepDone = true;

		$this->entityManager->flush($player);
	}

	public function isNextBuildingStepAlreadyDone(Player $player, int $buildingId, int $level): bool
	{
		$nextStepAlreadyDone = false;

		$playerBases = $this->orbitalBaseRepository->getPlayerBases($player);
		foreach ($playerBases as $orbitalBase) {
			if ($this->buildingLevelHandler->getBuildingLevel($orbitalBase, $buildingId) >= $level) {
				$nextStepAlreadyDone = true;
				break;
			} else {
				// verify in the queue
				$buildingQueues = $this->buildingQueueRepository->getBaseQueues($orbitalBase);
				foreach ($buildingQueues as $buildingQueue) {
					if ($buildingQueue->buildingNumber == $buildingId and $buildingQueue->targetLevel >= $level) {
						$nextStepAlreadyDone = true;
						break;
					}
				}
			}
		}

		return $nextStepAlreadyDone;
	}

	public function isNextTechnoStepAlreadyDone(Player $player, int $technoId, int $level = 1): bool
	{
		$technology = $this->technologyRepository->getPlayerTechnology($player);
		if ($technology->getTechnology($technoId) >= $level) {
			return true;
		}
		// verify in the queue
		if (null !== $this->technologyQueueRepository->getPlayerTechnologyQueue($player, $technoId)) {
			return true;
		}

		return false;
	}
}
