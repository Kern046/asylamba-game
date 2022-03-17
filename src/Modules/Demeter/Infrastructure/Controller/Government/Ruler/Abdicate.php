<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government\Ruler;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Utils;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Abdicate extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ColorManager $colorManager,
		PlayerManager $playerManager,
		NotificationManager $notificationManager,
		EntityManager $entityManager,
	): Response {
		$rPlayer = $request->request->get('rplayer');

		$faction = $colorManager->get($currentPlayer->getRColor());

		if (!$currentPlayer->isRuler()) {
			throw $this->createAccessDeniedException('Vous n\'êtes pas le chef de votre faction.');
		}
		if ($faction->isDemocratic()) {
			if ($faction->isInMandate()) {
				$date = new \DateTime(Utils::now());
				$date->modify('-' . $faction->mandateDuration . ' second');
				$date = $date->format('Y-m-d H:i:s');
				$faction->dLastElection = $date;
				$this->addFlash('success', 'Des élections anticipées vont être lancées.');
			} else {
				throw new ErrorException('Des élections sont déjà en cours.');
			}
		} else {
			if ($rPlayer !== FALSE) {
				if (($heir = $playerManager->get($rPlayer)) !== null) {
					if ($heir->getRColor() === $faction->getId()) {
						if ($heir->isParliamentMember()) {
							if ($faction->isInMandate()) {
								$heir->status = Player::CHIEF;
								// The player is now a member of Parliament
								$currentPlayer->status = Player::PARLIAMENT;
								$request->getSession()->get('playerInfo')->add('status', Player::PARLIAMENT);

								$statusArray = ColorResource::getInfo($heir->rColor, 'status');
								$notif = new Notification();
								$notif->setRPlayer($rPlayer);
								$notif->setTitle('Héritier du Trône.');
								$notif->addBeg()
									->addTxt('Vous avez été choisi par le ' . $statusArray[5] . ' de votre faction pour être son successeur, vous prenez la tête du gouvernement immédiatement.');
								$notificationManager->add($notif);

								$entityManager->flush();
								$this->addFlash('success', $heir->name . ' est désigné comme votre successeur.');
							} else {
								throw new ErrorException('vous ne pouvez pas abdiquer pendant un putsch.');
							}
						} else {
							throw new ErrorException('Vous ne pouvez choisir qu\'un membre du sénat ou du gouvernement.');
						}
					} else {
						throw new ErrorException('Vous ne pouvez pas choisir un joueur d\'une autre faction.');
					}
				} else {
					throw new ErrorException('Ce joueur n\'existe pas.');
				}
			} else {
				throw new ErrorException('Informations manquantes.');
			}
		}
		return $this->redirect($request->headers->get('referer'));
	}
}
