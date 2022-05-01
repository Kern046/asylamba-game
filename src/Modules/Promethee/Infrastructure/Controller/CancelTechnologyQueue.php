<?php

namespace App\Modules\Promethee\Infrastructure\Controller;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Utils;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Manager\TechnologyQueueManager;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CancelTechnologyQueue extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		TechnologyHelper $technologyHelper,
		TechnologyQueueManager $technologyQueueManager,
		PlayerManager $playerManager,
		OrbitalBaseManager $orbitalBaseManager,
		EntityManager $entityManager,
		string $identifier,
	): Response {
		if ($technologyHelper->isATechnology($identifier)) {
			$placeTechnologyQueues = $technologyQueueManager->getPlaceQueues($currentBase->getId());

			$index = null;
			$targetLevel = 0;
			$nbQueues = count($placeTechnologyQueues);
			for ($i = 0; $i < $nbQueues; ++$i) {
				$queue = $placeTechnologyQueues[$i];
				// get the queue to delete
				if ($queue->technology == $identifier and $queue->targetLevel > $targetLevel) {
					$index = $i;
					$targetLevel = $queue->targetLevel;
					$dStart = $queue->dStart;
				}
			}

			// if it's the first, the next must restart by now
			if (0 == $index) {
				$dStart = Utils::now();
			}

			if (null !== $index) {
				// shift
				for ($i = $index + 1; $i < $nbQueues; ++$i) {
					$queue = $placeTechnologyQueues[$i];

					// $oldDate = $queue->dEnd;
					$queue->dEnd = Utils::addSecondsToDate($dStart, Utils::interval($queue->dStart, $queue->dEnd, 's'));
					$queue->dStart = $dStart;
					// @TODO handle rescheduling
					// $scheduler->reschedule($queue, $queue->dEnd, $oldDate);

					$dStart = $queue->dEnd;
				}

				// @TODO handle cancellation
				// $scheduler->cancel($placeTechnologyQueues[$index], $placeTechnologyQueues[$index]->getEndedAt());
				$entityManager->remove($placeTechnologyQueues[$index]);
				$entityManager->flush(TechnologyQueue::class);

				$technologyResourceRefund = $this->getParameter('promethee.technology_queue.resource_refund');
				$technologyCreditRefund = $this->getParameter('promethee.technology_queue.credit_refund');
				// rends les ressources et les crédits au joueur
				$resourcePrice = $technologyHelper->getInfo($identifier, 'resource', $targetLevel);
				$resourcePrice *= $technologyResourceRefund;
				$orbitalBaseManager->increaseResources($currentBase, $resourcePrice, true);
				$creditPrice = $technologyHelper->getInfo($identifier, 'credit', $targetLevel);
				$creditPrice *= $technologyCreditRefund;
				$playerManager->increaseCredit($currentPlayer, $creditPrice);
				$this->addFlash('success', 'Construction annulée, vous récupérez le '.$technologyResourceRefund * 100 .'% des ressources ainsi que le '.$technologyCreditRefund * 100 .'% des crédits investis pour le développement');

				return $this->redirect($request->headers->get('referer'));
			} else {
				throw new ErrorException('impossible d\'annuler la technologie');
			}
		} else {
			throw new ErrorException('la technologie indiquée n\'est pas valide');
		}
	}
}
