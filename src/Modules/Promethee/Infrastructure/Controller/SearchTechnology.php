<?php

namespace App\Modules\Promethee\Infrastructure\Controller;

use App\Classes\Library\Game;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Domain\Repository\ResearchRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Manager\ResearchManager;
use App\Modules\Promethee\Manager\TechnologyQueueManager;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class SearchTechnology extends AbstractController
{
	public function __construct(
		private readonly TechnologyHelper $technologyHelper,
		private readonly ResearchManager $researchManager,
		private readonly ResearchRepositoryInterface $researchRepository,
	) {

	}

	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		OrbitalBase $currentBase,
		TechnologyQueueManager $technologyQueueManager,
		TechnologyQueueRepositoryInterface $technologyQueueRepository,
		TechnologyRepositoryInterface $technologyRepository,

		OrbitalBaseManager $orbitalBaseManager,
		PlayerManager $playerManager,
		DurationHandler $durationHandler,
		string $identifier,
	): Response
	{
		if (!$this->technologyHelper->isATechnology($identifier) || $this->technologyHelper->isATechnologyNotDisplayed($identifier)) {
			throw new BadRequestHttpException('la technologie indiquée n\'est pas valide');
		}
		if ($technologyQueueRepository->getPlayerTechnologyQueue($currentPlayer, $identifier) !== null) {
			throw new ConflictHttpException('Cette technologie est déjà en construction');
		}
		$technos = $technologyRepository->getPlayerTechnology($currentPlayer);
		$targetLevel = $technos->getTechnology($identifier) + 1;
		// @TODO I think this piece of code is dead
		$technologyQueues = $technologyQueueRepository->getPlaceQueues($currentBase->place);
		$nbTechnologyQueues = count($technologyQueues);
		foreach ($technologyQueues as $technologyQueue) {
			if ($technologyQueue->technology == $identifier) {
				++$targetLevel;
			}
		}


		if (!$this->haveRights($currentBase, $currentPlayer, $nbTechnologyQueues, $identifier, $targetLevel)) {
			throw $this->createAccessDeniedException(
				'les conditions ne sont pas remplies pour développer une technologie',
			);
		}
		// construit la nouvelle techno
		$time = $this->technologyHelper->getInfo($identifier, 'time', $targetLevel);
		$bonusPercent = $currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::TECHNOSPHERE_SPEED);
		if (ColorResource::APHERA == $currentPlayer->faction->identifier) {
			// bonus if the player is from Aphera
			$bonusPercent += ColorResource::BONUS_APHERA_TECHNO;
		}

		// ajout du bonus du lieu
		$bonusPercent += Game::getImprovementFromScientificCoef($currentBase->place->coefHistory);
		$bonus = round($time * $bonusPercent / 100);

		$createdAt =
			(0 === $nbTechnologyQueues)
				? new \DateTimeImmutable()
				: $technologyQueues[$nbTechnologyQueues - 1]->getEndDate();;
		$tq = new TechnologyQueue(
			id: Uuid::v4(),
			player: $currentPlayer,
			place: $currentBase->place,
			technology: $identifier,
			targetLevel: $targetLevel,
			startedAt: $createdAt,
			endedAt: $durationHandler->getDurationEnd($createdAt, round($time - $bonus)),
		);
		$technologyQueueManager->add($tq, $currentPlayer);

		$orbitalBaseManager->decreaseResources($currentBase, $this->technologyHelper->getInfo($identifier, 'resource', $targetLevel));

		$playerManager->decreaseCredit($currentPlayer, $this->technologyHelper->getInfo($identifier, 'credit', $targetLevel));

		// alerte
		$this->addFlash('success', 'Développement de la technologie programmée');

		return $this->redirect($request->headers->get('referer'));
	}

	private function haveRights(
		OrbitalBase $currentBase,
		Player      $currentPlayer,
		int         $technologyQueuesCount,
		int         $identifier,
		int         $targetLevel,
	): bool {
		$research = $this->researchRepository->getPlayerResearch($currentPlayer);

		return $this->technologyHelper->haveRights($identifier, 'resource', $targetLevel, $currentBase->resourcesStorage)
			&& $this->technologyHelper->haveRights($identifier, 'credit', $targetLevel, $currentPlayer->getCredits())
			&& $this->technologyHelper->haveRights($identifier, 'queue', $currentBase, $technologyQueuesCount)
			&& $this->technologyHelper->haveRights($identifier, 'levelPermit', $targetLevel)
			&& $this->technologyHelper->haveRights($identifier, 'technosphereLevel', $currentBase->levelTechnosphere)
			&& $this->technologyHelper->haveRights($identifier, 'research', $targetLevel, $this->researchManager->getResearchList($research))
			&& $this->technologyHelper->haveRights($identifier, 'maxLevel', $targetLevel)
			&& $this->technologyHelper->haveRights($identifier, 'baseType', $currentBase->typeOfBase);
	}
}
