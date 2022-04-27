<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CancelLaw extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        LawManager $lawManager,
        int $id,
    ): Response {
        if (($law = $lawManager->get($id)) === null) {
            throw $this->createNotFoundException('Cette loi n\'existe pas.');
        }
        if ($currentPlayer->getStatus() != LawResources::getInfo($law->getType(), 'department')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit d\'annuler cette loi.');
        }
        // @TODO implement law cancellation
        return $this->redirect($request->headers->get('referer'));
    }
}
