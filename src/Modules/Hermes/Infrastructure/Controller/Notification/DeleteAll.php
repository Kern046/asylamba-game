<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Notification;

use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeleteAll extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		NotificationManager $notificationManager,
	): Response {
		$nbr = $notificationManager->deleteByRPlayer($currentPlayer);

		if ($nbr > 1) {
			$this->addFlash('success', $nbr . ' notifications ont été supprimées.');
		} else if ($nbr == 1) {
			$this->addFlash('success', 'Une notification a été supprimée.');
		} else {
			$this->addFlash('success', 'Toutes vos notifications ont déjà été supprimées.');
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
