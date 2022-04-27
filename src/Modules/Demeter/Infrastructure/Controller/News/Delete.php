<?php

namespace App\Modules\Demeter\Infrastructure\Controller\News;

use App\Classes\Entity\EntityManager;
use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Delete extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        EntityManager $entityManager,
        FactionNewsManager $factionNewsManager,
        int $id,
    ): Response {
        if (($factionNew = $factionNewsManager->get($id)) === null) {
            throw $this->createNotFoundException('Cette annonce n\'existe pas.');
        }

        if (!$currentPlayer->isGovernmentMember() || $currentPlayer->getRColor() !== $factionNew->rFaction) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer cette annonce');
        }

        $entityManager->remove($factionNew);
        $this->addFlash('success', 'L\'annonce a bien été supprimée.');

        return $this->redirect($request->headers->get('referer'));
    }
}
