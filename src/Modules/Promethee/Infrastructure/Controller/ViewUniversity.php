<?php

namespace App\Modules\Promethee\Infrastructure\Controller;

use App\Modules\Promethee\Manager\ResearchManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewUniversity extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        ResearchManager $researchManager,
    ): Response {
        $session = $request->getSession();

        $researchManager->load(['rPlayer' => $session->get('playerId')]);

        return $this->render('pages/promethee/university.html.twig', [
            'university_investment_bonus' => $session->get('playerBonus')->get(PlayerBonus::UNI_INVEST),
            'research' => $researchManager->get(0),
        ]);
    }
}
