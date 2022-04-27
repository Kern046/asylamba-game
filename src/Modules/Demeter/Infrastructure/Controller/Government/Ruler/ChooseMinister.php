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

class ChooseMinister extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        EntityManager $entityManager,
        PlayerManager $playerManager,
        NotificationManager $notificationManager,
        int $department,
    ): Response {
        $rPlayer = $request->request->get('rplayer');

        if (null !== $rPlayer) {
            if (($minister = $playerManager->getGovernmentMember($currentPlayer->getRColor(), $department)) === null) {
                if ($currentPlayer->isRuler()) {
                    if (($appointee = $playerManager->get($rPlayer)) !== null) {
                        if ($appointee->rColor == $currentPlayer->getRColor()) {
                            if (Player::PARLIAMENT == $appointee->status) {
                                if ($department > Player::PARLIAMENT && $department < Player::CHIEF) {
                                    $appointee->status = $department;

                                    $statusArray = ColorResource::getInfo($appointee->rColor, 'status');
                                    $notif = new Notification();
                                    $notif->setRPlayer($rPlayer);
                                    $notif->setTitle('Nomination au gouvernement');
                                    $notif->addBeg()
                                        ->addTxt('Vous avez été choisi pour être le '.$statusArray[$department - 1].' de votre faction.');
                                    $notificationManager->add($notif);

                                    $entityManager->flush($appointee);
                                    $this->addFlash('success', $appointee->name.' a rejoint votre gouvernement.');

                                    return $this->redirect($request->headers->get('referer'));
                                } else {
                                    throw new ErrorException('Ce département est inconnu.');
                                }
                            } else {
                                throw new ErrorException('Vous ne pouvez choisir qu\'un membre du sénat.');
                            }
                        } else {
                            throw new ErrorException('Vous ne pouvez pas choisir un joueur d\'une autre faction.');
                        }
                    } else {
                        throw new ErrorException('Ce joueur n\'existe pas.');
                    }
                } else {
                    throw new ErrorException('Vous n\'êtes pas le chef de votre faction.');
                }
            } else {
                throw new ErrorException('Quelqu\'un occupe déjà ce poste.');
            }
        } else {
            throw new ErrorException('Informations manquantes.');
        }
    }
}
