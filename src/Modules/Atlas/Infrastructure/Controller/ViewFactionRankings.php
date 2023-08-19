<?php

namespace App\Modules\Atlas\Infrastructure\Controller;

use App\Classes\Library\Utils;
use App\Modules\Atlas\Domain\Repository\FactionRankingRepositoryInterface;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Atlas\Repository\RankingRepository;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewFactionRankings extends AbstractController
{
	public function __invoke(
		DurationHandler $durationHandler,
		FactionRankingRepositoryInterface $factionRankingRepository,
		RankingRepository $rankingRepository,
	): Response {
		// @TODO Replace this parameter by a dynamic field
		$serverStartTime = new \DateTimeImmutable($this->getParameter('server_start_time'));
		$hoursBeforeRankingStart = $this->getParameter('hours_before_start_of_ranking');

		$ranking = $rankingRepository->getLastRanking();

		$pointsRankings = $factionRankingRepository->getRankingsByField($ranking, 'pointsPosition');
		$generalRankings = $factionRankingRepository->getRankingsByField($ranking, 'generalPosition');
		$wealthRankings = $factionRankingRepository->getRankingsByField($ranking, 'wealthPosition');
		$territorialRankings = $factionRankingRepository->getRankingsByField($ranking, 'territorialPosition');

		$bestFaction = ($durationHandler->getHoursDiff($serverStartTime, new \DateTimeImmutable()) > $hoursBeforeRankingStart)
			? $pointsRankings[0] ?? null
			: $generalRankings[0] ?? null;

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
