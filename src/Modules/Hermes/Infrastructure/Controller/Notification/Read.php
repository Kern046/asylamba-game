<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Notification;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\FormException;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Read extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		NotificationManager $notificationManager,
		EntityManager $entityManager,
		int $id,
	): Response {
		if (($notification = $notificationManager->get($id)) !== null && $notification->rPlayer === $currentPlayer->getId()) {
			$notification->setReaded(1);
			$entityManager->flush($notification);
		} else {
			throw new FormException('Cette notification ne vous appartient pas');
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
