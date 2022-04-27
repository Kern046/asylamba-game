<?php

namespace App\Modules\Athena\Infrastructure\Controller\Ship;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Utils;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\ShipQueueManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CancelQueue extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        OrbitalBase $currentBase,
        OrbitalBaseManager $orbitalBaseManager,
        ShipQueueManager $shipQueueManager,
        EntityManager $entityManager,
        int $id,
    ): Response {
        $dock = $request->query->get('dock');

        if (false !== $dock) {
            if (intval($dock) > 0 and intval($dock) < 4) {
                $shipQueues = $shipQueueManager->getByBaseAndDockType($currentBase->getId(), $dock);
                $nbShipQueues = count($shipQueues);

                $index = null;
                for ($i = 0; $i < $nbShipQueues; ++$i) {
                    $shipQueue = $shipQueues[$i];
                    // get the index of the queue
                    if ($shipQueue->id === $id) {
                        $index = $i;
                        $dStart = $shipQueue->dStart;
                        $shipNumber = $shipQueue->shipNumber;
                        $dockType = $shipQueue->dockType;
                        $quantity = $shipQueue->quantity;
                        break;
                    }
                }

                // if it's the first, the next must restart by now
                if (0 == $index) {
                    $dStart = Utils::now();
                }

                if (null !== $index) {
                    // shift
                    for ($i = $index + 1; $i < $nbShipQueues; ++$i) {
                        $shipQueue = $shipQueues[$i];

                        $oldDate = $shipQueue->dEnd;
                        $shipQueue->dEnd = Utils::addSecondsToDate($dStart, Utils::interval($shipQueue->dStart, $shipQueue->dEnd, 's'));
                        $shipQueue->dStart = $dStart;

                        // @TODO handle rescheduling
                        // $scheduler->reschedule($shipQueue, $shipQueue->dEnd, $oldDate);

                        $dStart = $shipQueue->dEnd;
                    }

                    // @TODO handle cancellation
                    // $scheduler->cancel($shipQueues[$index], $shipQueues[$index]->dEnd);
                    $entityManager->remove($shipQueues[$index]);
                    $entityManager->flush(ShipQueue::class);
                    // give a part of the resources back
                    $resourcePrice = ShipResource::getInfo($shipNumber, 'resourcePrice');
                    if (1 == $dockType) {
                        $resourcePrice *= $quantity;
                    }
                    $shipResourceRefund = $this->getParameter('athena.building.ship_queue_resource_refund');
                    $resourcePrice *= $shipResourceRefund;

                    $orbitalBaseManager->increaseResources($currentBase, $resourcePrice, true);

                    $this->addFlash('success', 'Commande annulée, vous récupérez le '.$shipResourceRefund * 100 .'% du montant investi pour la construction');

                    return $this->redirect($request->headers->get('referer'));
                } else {
                    throw new ErrorException('suppression de vaisseau impossible');
                }
            } else {
                throw new ErrorException('suppression de vaisseau impossible - chantier invalide');
            }
        } else {
            throw new FormException('pas assez d\'informations pour enlever un vaisseau de la file d\'attente');
        }
    }
}
