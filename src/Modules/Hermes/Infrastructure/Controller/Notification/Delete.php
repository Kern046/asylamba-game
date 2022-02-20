<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Notification;

use App\Classes\Entity\EntityManager;
use App\Modules\Hermes\Manager\NotificationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Delete extends AbstractController
{
	public function __invoke(
		Request $request,
		NotificationManager $notificationManager,
		EntityManager $entityManager,
		int $id,
	): Response {
		$notification = $notificationManager->get($id);
		$entityManager->remove($notification);
		$entityManager->flush($notification);

		return ($request->isXmlHttpRequest())
			? new Response('', 204)
			: $this->redirect($request->headers->get('referer'));
	}
}
