<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Notification;

use App\Classes\Entity\EntityManager;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Archive extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		NotificationManager $notificationManager,
		EntityManager $entityManager,
		int $id,
	): Response {
		if (null === ($notification = $notificationManager->get($id))) {
			throw new NotFoundHttpException('Notification not found');
		}
		if ($notification->getRPlayer() !== $currentPlayer->getId()) {
			throw new AccessDeniedHttpException();
		}
		$notification->setArchived(!$notification->getArchived());
		$entityManager->flush($notification);

		return ($request->isXmlHttpRequest())
			? new Response('', 204)
			: $this->redirect($request->headers->get('referer'));
	}
}
