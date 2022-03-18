<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Gaia\Manager\SectorManager;
use App\Modules\Zeus\Manager\CreditTransactionManager;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewGovernment extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		FactionNewsManager $factionNewsManager,
		CreditTransactionManager $creditTransactionManager,
		ColorManager $colorManager,
		PlayerManager $playerManager,
		SectorManager $sectorManager,
	): Response {
		if (!$currentPlayer->isGovernmentMember()) {
			throw $this->createAccessDeniedException();
		}
		$faction = $colorManager->get($currentPlayer->getRColor());

		$creditTransactionManager->load(
			['rSender' => $faction->id, 'type' => CreditTransaction::TYP_F_TO_P],
			['dTransaction', 'DESC'],
			[0, 20]
		);

		return $this->render('pages/demeter/faction/government.html.twig', [
			'faction' => $faction,
			'parsed_description' => $colorManager->getParsedDescription($faction),
			'credit_transactions' => $creditTransactionManager->getAll(),
			'senators' => $playerManager->getParliamentMembers($faction->id),
			'faction_sectors' => $sectorManager->getFactionSectors($faction->id),
			'faction_news' => $factionNewsManager->getFactionNews($faction->id),
			'faction_news_to_edit' => $request->query->has('news') ? $factionNewsManager->get($request->query->get('news')) : null,
			'faction_members' => $playerManager->getFactionPlayersByName($faction->id),
			'members_count' =>  $playerManager->countByFactionAndStatements($faction->id, [Player::ACTIVE]),
			'government_members' => $playerManager->getGovernmentMembers($faction->id),
			'total_laws_count' => LawResources::size(),
		]);
	}
}
