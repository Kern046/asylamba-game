<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Notification;

use App\Classes\Entity\EntityManager;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadAll extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		NotificationManager $notificationManager,
		EntityManager $entityManager,
	): Response {

		$notifications = $notificationManager->getUnreadNotifications($currentPlayer->getId());
		$nbNotifications = count($notifications);

		foreach ($notifications as $notification) {
			$notification->setReaded(1);
		}

		$entityManager->flush(Notification::class);

		if ($nbNotifications > 1) {
			$this->addFlash('success', $nbNotifications . ' notifications ont été marquées comme lues.');
		} else if ($nbNotifications == 1) {
			$this->addFlash('success', 'Une notification a été marquée comme lue.');
		} else {
			$this->addFlash('success', 'Toutes vos notifications ont déjà été lues.');
		}

		return ($request->isXmlHttpRequest())
			? new Response('', 204)
			: $this->redirect($request->headers->get('referer'));
	}
}
