<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Classes\Entity\EntityManager;
use App\Classes\Library\Utils;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\BuildingQueueManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Cancel extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		OrbitalBaseHelper $orbitalBaseHelper,
		OrbitalBaseManager $orbitalBaseManager,
		BuildingQueueManager $buildingQueueManager,
		EntityManager $entityManager,
		int $identifier,
	): Response {
		if ($orbitalBaseHelper->isABuilding($identifier)) {
			$buildingQueues = $buildingQueueManager->getBaseQueues($currentBase->getId());

			$index = NULL;
			$nbBuildingQueues = count($buildingQueues);
			for ($i = 0; $i < $nbBuildingQueues; $i++) {
				$queue = $buildingQueues[$i];
				# get the last element from the correct building
				if ($queue->buildingNumber == $identifier) {
					$index = $i;
					$targetLevel = $queue->targetLevel;
					$dStart = $queue->dStart;
				}
			}

			# if it's the first, the next must restart by now
			if ($index == 0) {
				$dStart = Utils::now();
			}

			if ($index !== NULL) {
				# shift
				for ($i = $index + 1; $i < $nbBuildingQueues; $i++) {
					$queue = $buildingQueues[$i];

					$oldDate = $queue->dEnd;
					$queue->dEnd = Utils::addSecondsToDate($dStart, Utils::interval($queue->dStart, $queue->dEnd, 's'));
					$queue->dStart = $dStart;

					// @TODO handle rescheduling
					// $scheduler->reschedule($queue, $queue->dEnd, $oldDate);

					$dStart = $queue->dEnd;
				}

				// @TODO handle cancellation
				// $scheduler->cancel($buildingQueues[$index], $buildingQueues[$index]->dEnd);
				$entityManager->remove($buildingQueues[$index]);
				$entityManager->flush(BuildingQueue::class);

				$buildingResourceRefund = $this->getParameter('athena.building.building_queue_resource_refund');
				// give the resources back
				$resourcePrice = $orbitalBaseHelper->getBuildingInfo($identifier, 'level', $targetLevel, 'resourcePrice');
				$resourcePrice *= $buildingResourceRefund;
				$orbitalBaseManager->increaseResources($currentBase, $resourcePrice, TRUE);
				$this->addFlash('success', 'Construction annulée, vous récupérez le ' . $buildingResourceRefund * 100 . '% du montant investi pour la construction');

				return $this->redirect($request->headers->get('referer'));
			} else {
				throw new ConflictHttpException('suppression de bâtiment impossible');
			}
		} else {
			throw new BadRequestHttpException('le bâtiment indiqué n\'est pas valide');
		}
	}
}
