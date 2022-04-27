<?php

namespace App\Modules\Zeus\Infrastructure\Controller;

use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AutocompletePlayers extends AbstractController
{
    public function __invoke(
        Request $request,
        PlayerManager $playerManager,
    ): Response {
        if (null === ($search = $request->query->get('q'))) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return $this->render('blocks/zeus/autocomplete_player.html.twig', [
            'players' => $playerManager->search($search),
        ]);
    }
}
