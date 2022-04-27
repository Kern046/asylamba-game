<?php

namespace App\Modules\Demeter\Infrastructure\Controller\News;

use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Edit extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        FactionNewsManager $factionNewsManager,
        int $id,
    ): Response {
        $content = $request->request->get('content');
        $title = $request->request->get('title');

        if (null !== $title && null !== $content) {
            if (($factionNew = $factionNewsManager->get($id)) !== null) {
                if ($currentPlayer->isGovernmentMember() && $currentPlayer->getRColor() === $factionNew->rFaction) {
                    $factionNew->title = $title;
                    $factionNewsManager->edit($factionNew, $content);

                    return $this->redirect($request->headers->get('referer'));
                } else {
                    throw new ErrorException('Vous n\'avez pas le droit pour créer une annonce.');
                }
            } else {
                throw new FormException('Cette annonce n\'existe pas.');
            }
        } else {
            throw new FormException('Manque d\'information.');
        }
    }
}
