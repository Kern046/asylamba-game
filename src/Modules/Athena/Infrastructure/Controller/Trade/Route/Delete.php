<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Delete extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        OrbitalBase $currentBase,
        CommercialRouteManager $commercialRouteManager,
        OrbitalBaseManager $orbitalBaseManager,
        NotificationManager $notificationManager,
        int $id,
    ): Response {
        if (null === ($cr = $commercialRouteManager->get($id))) {
            throw $this->createNotFoundException('Commercial route not found');
        }
        if ($cr->isProposed()) {
            throw new ConflictHttpException('Commercial route has not been established yet');
        }
        if ($cr->playerId1 !== $currentPlayer->getid() && $cr->playerId2 === $currentPlayer->getId()) {
            throw $this->createAccessDeniedException('This Commercial route does not belong to you');
        }
        if ($cr->getROrbitalBase() !== $currentBase->getId() && $cr->getROrbitalBaseLinked() !== $currentBase->getId()) {
            throw new ConflictHttpException('Commercial route does not belong to the current base');
        }
        $proposerBase = $orbitalBaseManager->get($cr->getROrbitalBase());
        $linkedBase = $orbitalBaseManager->get($cr->getROrbitalBaseLinked());
        if ($cr->getROrbitalBase() == $currentBase->getId()) {
            $notifReceiver = $linkedBase->getRPlayer();
            $myBaseName = $proposerBase->getName();
            $otherBaseName = $linkedBase->getName();
            $myBaseId = $proposerBase->getRPlace();
            $otherBaseId = $linkedBase->getRPlace();
        } else { // if ($cr->getROrbitalBaseLinked == $base) {
            $notifReceiver = $proposerBase->getRPlayer();
            $myBaseName = $linkedBase->getName();
            $otherBaseName = $proposerBase->getName();
            $myBaseId = $linkedBase->getRPlace();
            $otherBaseId = $proposerBase->getRPlace();
        }

        // perte du prestige pour les joueurs Négoriens
        // @TODO check if this code is used somewhere or not
        //				$S_PAM1 = $playerManager->getCurrentSession();
        //				$playerManager->newSession();
        //				$playerManager->load(array('id' => array($cr->playerId1, $cr->playerId2)));
        //				$exp = round($cr->getIncome() * $routeExperienceCoeff);
//
        //				$playerManager->changeSession($S_PAM1);
        // notification
        $n = new Notification();
        $n->setRPlayer($notifReceiver);
        $n->setTitle('Route commerciale détruite');
        $n->addBeg()->addLnk('embassy/player-'.$currentPlayer->getId(), $currentPlayer->getName())->addTxt(' annule les accords commerciaux entre ');
        $n->addLnk('map/place-'.$myBaseId, $myBaseName)->addTxt(' et ');
        $n->addLnk('map/place-'.$otherBaseId, $otherBaseName)->addTxt('.');
        $n->addSep()->addTxt('La route commerciale qui liait les deux bases orbitales est détruite, elle ne vous rapporte donc plus rien !');
        $n->addEnd();
        $notificationManager->add($n);

        // destruction de la route
        $commercialRouteManager->remove($cr);

        $this->addFlash('success', 'Route commerciale détruite');

        return $this->redirect($request->headers->get('referer'));
    }
}
