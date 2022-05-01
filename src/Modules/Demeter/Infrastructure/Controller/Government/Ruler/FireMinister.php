<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government\Ruler;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FireMinister extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		EntityManager $entityManager,
		PlayerManager $playerManager,
		NotificationManager $notificationManager,
		int $id,
	): Response {
		if ($currentPlayer->isRuler()) {
			if (($minister = $playerManager->get($id)) !== null) {
				if ($minister->rColor == $currentPlayer->getRColor()) {
					if ($minister->isGovernmentMember()) {
						$statusArray = ColorResource::getInfo($minister->rColor, 'status');
						$notif = new Notification();
						$notif->setRPlayer($id);
						$notif->setTitle('Eviction du gouvernement');
						$notif->addBeg()
							->addTxt('Vous avez été renvoyé du poste de '.$statusArray[$minister->status - 1].' de votre faction.');
						$notificationManager->add($notif);

						$minister->status = Player::PARLIAMENT;

						$entityManager->flush($minister);

						return $this->redirect($request->headers->get('referer'));
					} else {
						throw new ErrorException('Vous ne pouvez choisir qu\'un membre du gouvernement.');
					}
				} else {
					throw new ErrorException('Vous ne pouvez pas virer un joueur d\'une autre faction.');
				}
			} else {
				throw new ErrorException('Ce joueur n\'existe pas.');
			}
		} else {
			throw new ErrorException('Vous n\'êtes pas le chef de votre faction.');
		}
	}
}
