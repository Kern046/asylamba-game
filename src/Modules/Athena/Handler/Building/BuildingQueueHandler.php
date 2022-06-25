<?php

namespace App\Modules\Athena\Handler\Building;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Application\Handler\OrbitalBasePointsHandler;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Message\Building\BuildingQueueMessage;
use App\Modules\Zeus\Manager\PlayerManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class BuildingQueueHandler
{
	public function __construct(
		private PlayerManager                    $playerManager,
		private OrbitalBasePointsHandler         $orbitalBasePointsHandler,
		private OrbitalBaseRepositoryInterface   $orbitalBaseRepository,
		private BuildingQueueRepositoryInterface $buildingQueueRepository,
		private OrbitalBaseHelper                $orbitalBaseHelper,
		private BuildingLevelHandler             $buildingLevelHandler,
		private LoggerInterface                  $logger,
	) {
	}

	public function __invoke(BuildingQueueMessage $message): void
	{
		$this->logger->info('Handle building completion for queue {queueId}', [
			'queueId' => $message->getBuildingQueueId(),
		]);
		if (null === ($queue = $this->buildingQueueRepository->get($message->getBuildingQueueId()))) {
			return;
		}
		$orbitalBase = $queue->base;
		$player = $orbitalBase->player;
		$this->buildingLevelHandler->increaseBuildingLevel($orbitalBase, $queue->buildingNumber, $queue->targetLevel);
		$this->orbitalBasePointsHandler->updatePoints($orbitalBase);
		$this->orbitalBaseRepository->save($orbitalBase);
		// increase player experience
		$experience = $this->orbitalBaseHelper->getBuildingInfo($queue->buildingNumber, 'level', $queue->targetLevel, 'points');
		$this->playerManager->increaseExperience($player, $experience);

		// alert @TODO replace with Mercure
		//		if (($session = $this->clientManager->getSessionByPlayerId($player->getId())) !== null) {
		//			$session->addFlashbag('Construction de votre <strong>' . $this->orbitalBaseHelper->getBuildingInfo($queue->buildingNumber, 'frenchName') . ' niveau ' . $queue->targetLevel . '</strong> sur <strong>' . $orbitalBase->name . '</strong> terminée. Vous gagnez ' . $experience . ' point' . Format::addPlural($experience) . ' d\'expérience.', Flashbag::TYPE_GENERATOR_SUCCESS);
		//			$this->sessionWrapper->save($session);
		//		}
		$this->buildingQueueRepository->remove($queue);
		$this->logger->info('Construction done');
	}
}
