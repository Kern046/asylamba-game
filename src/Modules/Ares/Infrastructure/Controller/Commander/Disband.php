<?php

namespace App\Modules\Ares\Infrastructure\Controller\Commander;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Disband extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        CommanderManager $commanderManager,
        EntityManager $entityManager,
        int $id,
    ): Response {
        if (($commander = $commanderManager->get($id)) === null || $commander->rPlayer !== $currentPlayer->getId()) {
            throw new ErrorException('Ce commandant n\'existe pas ou ne vous appartient pas.');
        }
        if (1 !== $commander->statement) {
            throw new ErrorException('Vous ne pouvez pas retirer les vaisseaux à un officier en déplacement.');
        }

        // vider le commandant
        $commanderManager->emptySquadrons($commander);

        $this->addFlash('success', 'Vous avez vidé l\'armée menée par votre commandant '.$commander->getName().'.');

        $entityManager->flush();

        return $this->redirect($request->headers->get('referer'));
    }
}
