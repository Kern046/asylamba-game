<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Infrastructure\Validator\CanMakeBuilding;
use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Athena\Infrastructure\Validator\IsValidBuilding;
use App\Modules\Athena\Manager\BuildingQueueManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Build extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		BonusApplierInterface $bonusApplier,
		OrbitalBase $currentBase,
		OrbitalBaseHelper $orbitalBaseHelper,
		OrbitalBaseManager $orbitalBaseManager,
		TechnologyManager $technologyManager,
		TechnologyRepositoryInterface $technologyRepository,
		BuildingQueueManager $buildingQueueManager,
		BuildingQueueRepositoryInterface $buildingQueueRepository,
		BuildingLevelHandler $buildingLevelHandler,
		DurationHandler $durationHandler,
		ValidatorInterface $validator,
		int $identifier,
	): Response {
		if (!$orbitalBaseHelper->isABuilding($identifier)) {
			throw new BadRequestHttpException('le bâtiment indiqué n\'est pas valide');
		}
		$buildingQueues = $buildingQueueRepository->getBaseQueues($currentBase);
		$buildingQueuesCount = count($buildingQueues);

		$currentLevel = $buildingLevelHandler->getBuildingRealLevel($currentBase, $identifier, $buildingQueues);
		$targetLevel = $currentLevel + 1;
		$technos = $technologyRepository->getPlayerTechnology($currentPlayer);

		$buildingConstructionOrder = new BuildingConstructionOrder(
			orbitalBase: $currentBase,
			technology: $technos,
			buildingIdentifier: $identifier,
			targetLevel: $targetLevel,
		);

		$violations = $validator->validate($buildingConstructionOrder, new Sequentially([
			new IsValidBuilding(),
			new CanMakeBuilding($buildingQueuesCount),
		]));

		if (0 < $violations->count()) {
			throw new ValidationFailedException($buildingConstructionOrder, $violations);
		}

		$session = $request->getSession();
		if (0 === $buildingQueuesCount) {
			$startedAt = new \DateTimeImmutable();
		} else {
			$startedAt = $buildingQueues[$buildingQueuesCount - 1]->endedAt;
		}

		$time = $orbitalBaseHelper->getBuildingInfo($identifier, 'level', $targetLevel, 'time');
		$bonus = $bonusApplier->apply($time, PlayerBonusId::GENERATOR_SPEED);

		// build the new building
		$bq = new BuildingQueue(
			id: Uuid::v4(),
			base: $currentBase,
			buildingNumber: $identifier,
			targetLevel: $targetLevel,
			startedAt: $startedAt,
			endedAt: $durationHandler->getDurationEnd($startedAt, round($time - $bonus)),
		);

		$buildingQueueManager->add($bq);

		// debit resources
		$orbitalBaseManager->decreaseResources(
			$currentBase,
			$orbitalBaseHelper->getBuildingInfo($identifier, 'level', $currentLevel + 1, 'resourcePrice'),
		);

		// add the event in controller
		$session->get('playerEvent')->add($bq->getEndDate(), $this->getParameter('event_base'), $currentBase->id);

		$this->addFlash('success', 'Construction programmée');

		return $this->redirect($request->headers->get('referer'));
	}
}
