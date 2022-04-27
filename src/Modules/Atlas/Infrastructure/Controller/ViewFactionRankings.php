<?php

namespace App\Modules\Atlas\Infrastructure\Controller;

use App\Classes\Library\Utils;
use App\Modules\Atlas\Manager\FactionRankingManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewFactionRankings extends AbstractController
{
    public function __invoke(
        FactionRankingManager $factionRankingManager,
    ): Response {
        if (Utils::interval($this->getParameter('server_start_time'), Utils::now(), 'h') > $this->getParameter('hours_before_start_of_ranking')) {
            $factionRankingManager->loadLastContext([], ['pointsPosition', 'ASC'], [0, 1]);
        } else {
            $factionRankingManager->loadLastContext([], ['generalPosition', 'ASC'], [0, 1]);
        }
        $bestFaction = $factionRankingManager->get(0);

        $factionRankingManager->newSession();
        $factionRankingManager->loadLastContext([], ['pointsPosition', 'ASC']);
        $pointsRankings = $factionRankingManager->getAll();

        $factionRankingManager->newSession();
        $factionRankingManager->loadLastContext([], ['generalPosition', 'ASC']);
        $generalRankings = $factionRankingManager->getAll();

        $factionRankingManager->newSession();
        $factionRankingManager->loadLastContext([], ['wealthPosition', 'ASC']);
        $wealthRankings = $factionRankingManager->getAll();

        $factionRankingManager->newSession();
        $factionRankingManager->loadLastContext([], ['territorialPosition', 'ASC']);
        $territorialRankings = $factionRankingManager->getAll();

        return $this->render('pages/atlas/faction_rankings.html.twig', [
            'best_faction' => $bestFaction,
            'points_rankings' => $pointsRankings,
            'general_rankings' => $generalRankings,
            'wealth_rankings' => $wealthRankings,
            'territorial_rankings' => $territorialRankings,
            'points_to_win' => $this->getParameter('points_to_win'),
        ]);
    }
}
