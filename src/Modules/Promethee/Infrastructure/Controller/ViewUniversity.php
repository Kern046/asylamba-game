<?php

namespace App\Modules\Promethee\Infrastructure\Controller;

use App\Modules\Promethee\Domain\Repository\ResearchRepositoryInterface;
use App\Modules\Promethee\Manager\ResearchManager;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewUniversity extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		ResearchManager $researchManager,
		ResearchRepositoryInterface $researchRepository,
	): Response {
		return $this->render('pages/promethee/university.html.twig', [
			'university_investment_bonus' => $currentPlayerBonusRegistry
				->getPlayerBonus()->bonuses->get(PlayerBonusId::UNI_INVEST),
			'research' => $researchRepository->getPlayerResearch($currentPlayer),
		]);
	}
}
