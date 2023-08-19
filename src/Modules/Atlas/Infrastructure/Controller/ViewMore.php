<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Infrastructure\Controller;

use App\Modules\Atlas\Domain\Repository\PlayerRankingRepositoryInterface;
use App\Modules\Atlas\Domain\Repository\RankingRepositoryInterface;
use App\Modules\Atlas\Model\PlayerRanking;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ViewMore extends AbstractController
{
	#[Route(
		path: '/rankings/more',
		name: 'load_more_rankings',
		methods: Request::METHOD_GET,
	)]
	public function __invoke(
		Request $request,
		RankingRepositoryInterface $rankingRepository,
		PlayerRankingRepositoryInterface $playerRankingRepository,
	): Response {
		$direction = $request->query->get('dir') ?? throw new BadRequestHttpException('Missing dir');
		$current = $request->query->get('current') ?? throw new BadRequestHttpException('Missing current');
		$type = $request->query->get('type') ?? throw new BadRequestHttpException('Missing type');

		if (!in_array($direction, ['next', 'prev'])) {
			throw new BadRequestHttpException('Invalid direction');
		}

		if (!in_array($type, ['general', 'resources', 'xp', 'fight', 'armies', 'butcher', 'trader'])) {
			throw new BadRequestHttpException('Invalid type');
		}

		$ranking = $rankingRepository->getLastRanking() ?? throw new ConflictHttpException('No ranking available yet');

		// var
		$fty = ('xp' == $type)
			? 'experience'
			: $type;

		$bot = ('next' == $direction)
			? (($current - PlayerRanking::PAGE > 1) ? $current - PlayerRanking::PAGE : 1)
			: $current + 1;

		$size = (1 == $bot)
			? $current - 1
			: PlayerRanking::PAGE;

		$playerRankings = $playerRankingRepository->getRankingsByRange($ranking, $fty.'Position', $bot - 1, $size);

		return new Response();
	}
}
