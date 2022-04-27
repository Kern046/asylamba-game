<?php

namespace App\Modules\Ares\Infrastructure\Controller\Commander;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Fire extends AbstractController
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

        if (1 == $commander->statement) {
            // vider le commandant
            $commanderManager->emptySquadrons($commander);
            $commander->setStatement(4);

            $this->addFlash('success', 'Vous avez renvoyé votre commandant '.$commander->getName().'.');
        } else {
            $this->addFlash('error', 'Vous ne pouvez pas renvoyer un officier en déplacement.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('fleet_headquarters');
    }
}
