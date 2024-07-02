<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Athena\Application\Factory\BuildingQueueFactory;
use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\Building\BuildingDataHandler;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Infrastructure\Validator\CanMakeBuilding;
use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Athena\Infrastructure\Validator\IsValidBuilding;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Build extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		OrbitalBaseHelper $orbitalBaseHelper,
		OrbitalBaseManager $orbitalBaseManager,
		TechnologyRepositoryInterface $technologyRepository,
		BuildingDataHandler $buildingDataHandler,
		BuildingQueueRepositoryInterface $buildingQueueRepository,
		BuildingLevelHandler $buildingLevelHandler,
		BuildingQueueFactory $buildingQueueFactory,
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

		if (0 === $buildingQueuesCount) {
			$startedAt = new \DateTimeImmutable();
		} else {
			$startedAt = $buildingQueues[$buildingQueuesCount - 1]->endedAt;
		}

		// debit resources
		$orbitalBaseManager->decreaseResources(
			$currentBase,
			$buildingDataHandler->getBuildingResourceCost($identifier, $targetLevel),
		);

		$buildingQueue = $buildingQueueFactory->create(
			orbitalBase: $currentBase,
			identifier: $identifier,
			targetLevel: $targetLevel,
			startedAt: $startedAt,
		);

		// TODO remove this
		// add the event in controller
		$request->getSession()->get('playerEvent')->add($buildingQueue->getEndDate(), $this->getParameter('event_base'), $currentBase->id);

		$this->addFlash('success', 'Construction programmée');

		return $this->redirect($request->headers->get('referer'));
	}
}
