<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Classes\Library\Format;
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

class Refuse extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        OrbitalBase $currentBase,
        CommercialRouteManager $commercialRouteManager,
        OrbitalBaseManager $orbitalBaseManager,
        PlayerManager $playerManager,
        NotificationManager $notificationManager,
        int $baseId,
        int $id,
    ): Response {
        if (null === ($cr = $commercialRouteManager->getByIdAndDistantBase($id, $baseId))) {
            throw $this->createNotFoundException('Commercial route not found');
        }
        if (!$cr->isProposed()) {
            throw new ConflictHttpException('Commercial route has already been established');
        }
        $proposerBase = $orbitalBaseManager->get($cr->getROrbitalBase());
        $refusingBase = $orbitalBaseManager->get($cr->getROrbitalBaseLinked());

        // rend les crédits au proposant
        $playerManager->increaseCredit($playerManager->get($proposerBase->getRPlayer()), intval($cr->getPrice()));

        // notification
        $n = new Notification();
        $n->setRPlayer($proposerBase->getRPlayer());
        $n->setTitle('Route commerciale refusée');
        $n->addBeg()->addLnk('embassy/player-'.$currentPlayer->getId(), $currentPlayer->getName())->addTxt(' a refusé la route commerciale proposée entre ');
        $n->addLnk('map/place-'.$refusingBase->getRPlace(), $refusingBase->getName())->addTxt(' et ');
        $n->addLnk('map/place-'.$proposerBase->getRPlace(), $proposerBase->getName())->addTxt('.');
        $n->addSep()->addTxt('Les '.Format::numberFormat($cr->getPrice()).' crédits bloqués sont à nouveau disponibles.');
        $n->addEnd();
        $notificationManager->add($n);

        // destruction de la route
        $commercialRouteManager->remove($cr);
        $this->addFlash('success', 'Route commerciale refusée');

        return $this->redirect($request->headers->get('referer'));
    }
}
