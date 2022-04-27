<?php

namespace App\Modules\Demeter\Infrastructure\Controller\News;

use App\Classes\Entity\EntityManager;
use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Pin extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        EntityManager $entityManager,
        FactionNewsManager $factionNewsManager,
        int $id
    ): Response {
        $factionNews = $factionNewsManager->getFactionNews($currentPlayer->getRColor());
        $newExists = false;
        // This way of doing things remove all previous pins
        foreach ($factionNews as $factionNew) {
            if ($factionNew->id == $id) {
                $newExists = true;
                $factionNew->pinned = 1;
            } else {
                $factionNew->pinned = 0;
            }
        }
        if (true !== $newExists) {
            throw new NotFoundHttpException('Cette annonce n\'existe pas.');
        }
        $entityManager->flush();

        return $this->redirect($request->headers->get('referer'));
    }
}
