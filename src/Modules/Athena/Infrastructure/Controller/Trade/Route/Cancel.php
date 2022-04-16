<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Classes\Entity\EntityManager;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Cancel extends AbstractController
{
	public function __invoke(
		Request $request,
		CommercialRouteManager $commercialRouteManager,
		PlayerManager $playerManager,
		NotificationManager $notificationManager,
		OrbitalBaseManager $orbitalBaseManager,
		EntityManager $entityManager,
		OrbitalBase $currentBase,
		Player $currentPlayer,
		int $id,
	): Response {
		$cr = $commercialRouteManager->getByIdAndBase($id, $currentBase->getId());
		if ($cr === null) {
			throw $this->createNotFoundException('Commercial route not found');
		}
		if (!$cr->isProposed()) {
			throw new ConflictHttpException('Commercial route has already been established');
		}
		$routeCancelRefund = $this->getParameter('athena.trade.route.cancellation_refund');
		$proposerBase = $orbitalBaseManager->get($cr->getROrbitalBase());
		$linkedBase = $orbitalBaseManager->get($cr->getROrbitalBaseLinked());

		//rend 80% des crédits investis
		$playerManager->increaseCredit($currentPlayer, round($cr->getPrice() * $routeCancelRefund));

		//notification
		$n = new Notification();
		$n->setRPlayer($linkedBase->getRPlayer());
		$n->setTitle('Route commerciale annulée');

		$n->addBeg()->addLnk('embassy/player-' . $currentPlayer->getId(), $currentPlayer->getName())->addTxt(' a finalement retiré la proposition de route commerciale qu\'il avait faite entre ');
		$n->addLnk('map/place-' . $linkedBase->getRPlace(), $linkedBase->getName())->addTxt(' et ');
		$n->addLnk('map/place-' . $proposerBase->getRPlace(), $proposerBase->getName());
		$n->addEnd();
		$notificationManager->add($n);

		//destruction de la route
		$commercialRouteManager->remove($cr);
		$this->addFlash('success', 'Route commerciale annulée. Vous récupérez les ' . $routeCancelRefund * 100 . '% du montant investi.');

		$entityManager->flush();

		return $this->redirect($request->headers->get('referer'));
	}
}
