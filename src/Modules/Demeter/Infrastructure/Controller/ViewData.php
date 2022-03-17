<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\Format;
use App\Classes\Redis\RedisManager;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Manager\LiveReportManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Resource\CommanderResources;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Athena\Manager\CommercialTaxManager;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Atlas\Manager\FactionRankingManager;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Gaia\Galaxy\GalaxyConfiguration;
use App\Modules\Gaia\Manager\SectorManager;
use App\Modules\Zeus\Manager\CreditTransactionManager;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// @TODO split this controller into multiple ones
class ViewData extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ColorManager $colorManager,
		CommanderManager $commanderManager,
		CommercialRouteManager $commercialRouteManager,
		CommercialTaxManager $commercialTaxManager,
		CreditTransactionManager $creditTransactionManager,
		LiveReportManager $liveReportManager,
		FactionRankingManager $factionRankingManager,
		GalaxyConfiguration $galaxyConfiguration,
		SectorManager $sectorManager,
		RedisManager $redisManager,
	): Response {
		$faction = $colorManager->get($currentPlayer->getRColor());

		$factionRankingManager->loadByRequest(
			'WHERE rFaction = ? ORDER BY rRanking DESC LIMIT 0, 20',
			array($faction->id)
		);

		$creditBase = 0;
		for ($i = 0; $i < $factionRankingManager->size(); $i++) {
			if ($creditBase < $factionRankingManager->get($i)->wealth) {
				$creditBase = $factionRankingManager->get($i)->wealth;
			}
		}
		// @TODO factoriser cette formule
		$creditBase += $creditBase * 12 / 100;

# load
		$creditTransactionManager->newSession();
		$creditTransactionManager->load(
			['rReceiver' => $faction->id, 'type' => CreditTransaction::TYP_FACTION],
			['dTransaction', 'DESC'],
			[0, 20],
		);
		$membersDonations = $creditTransactionManager->getAll();


		$creditTransactionManager->newSession();
		$creditTransactionManager->load(
			['rSender' => $faction->id, 'type' => CreditTransaction::TYP_F_TO_P],
			['dTransaction', 'DESC'],
			[0, 20],
		);
		$factionDonations = $creditTransactionManager->getAll();

		$commercialTaxManager->newSession();
		$commercialTaxManager->load(array('faction' => $faction->id), array('importTax', 'ASC'));
		$importaxes = $commercialTaxManager->getAll();

		$commercialTaxManager->newSession();
		$commercialTaxManager->load(array('faction' => $faction->id), array('exportTax', 'ASC'));
		$exportTaxes = $commercialTaxManager->getAll();

		$commanderStats = $commanderManager->getFactionCommanderStats($faction->getId());
		$fleetStats = $commanderManager->getFactionFleetStats($faction->getId());


		$totalPEV = 0;
		for ($i = 0; $i < 12; $i++) {
			$totalPEV += $fleetStats['nbs' . $i] * ShipResource::getInfo($i, 'pev');
		}

		$factions = $colorManager->getAll();
		$sectors = $sectorManager->getAll();
		$mapData = $this->getTacticalMapData($faction->getId(), $factions, $sectors, $redisManager);

		return $this->render('pages/demeter/faction/data.html.twig', [
			'faction' => $faction,
			'credit_base' => $creditBase,
			'faction_rankings' => $factionRankingManager->getAll(),
			'members_donations' => $membersDonations,
			'faction_donations' => $factionDonations,
			'faction_sectors' => $sectorManager->getFactionSectors($faction->id),
			'rc_data' => $commercialRouteManager->getCommercialRouteFactionData($faction->id),
			'rc_diplomatic_data' => $this->getCommercialRoutesDiplomaticData($faction, $commercialRouteManager),
			'import_taxes' => $importaxes,
			'export_taxes' => $exportTaxes,
			'commanders_ranks_count' => CommanderResources::size(),
			'base_commander_level' => Commander::CMDBASELVL,
			'commander_stats' => $commanderStats,
			'fleet_stats' => $fleetStats,
			'total_pev' => $totalPEV,
			'attack_reports' => $liveReportManager->getFactionAttackReports($faction->getId()),
			'defense_reports' => $liveReportManager->getFactionDefenseReports($faction->getId()),
			'sectors' => $sectors,
			'map_scale' => 750 / $galaxyConfiguration->galaxy['size'],
			'map_data_types' => $mapData['types'],
			'map_data_scores' => $mapData['scores'],
			'map_data_percents' => $mapData['percents'],
			'galaxy_configuration' => $galaxyConfiguration,
			'factions' => $factions,
			'points_to_win' => $this->getParameter('points_to_win'),
			'diplomacy_statements' => [
				Color::ENEMY => 'En guerre',
				Color::ALLY => 'Allié',
				Color::PEACE => 'Pacte de non-agression',
				Color::NEUTRAL => 'Neutre'
			],
			'laws_count' => LawResources::size(),
		]);
	}

	private function getTacticalMapData(int $factionId, array $factions, array $sectors, RedisManager $redisManager): array
	{
		$scores = $percents = [];

		$types = [
			'Secteurs conquis' => [],
			'Secteurs en balance' => [],
		];

		foreach (array_keys($types) as $type) {
			foreach ($sectors as $key => $sector) {
				$percents = ['color' . $factionId => 0];
				$scores = unserialize($redisManager->getConnection()->get('sector:' . $sector->getId()));

				if (!isset($scores[$factionId]) && $sector->getRColor() !== $factionId) {
					unset($sectors[$key]);
					continue;
				}
				if ($type === 'Secteurs conquis' && $sector->getRColor() !== $factionId) {
					continue;
				}

				foreach ($factions as $f) {
					if ($f->id === 0 || !isset($scores[$f->id])) {
						continue;
					}
					$percents['color' . $f->id] = round(Format::percent($scores[$f->id], array_sum($scores), false));
				}

				arsort($percents);

				if ($sector->getRColor() == $factionId || ($scores[$factionId] > 0)) {
					$types[$type] = $sector;
				}
			}
		}


		return [
			'types' => $types,
			'scores' => $scores,
			'percents' => $percents,
		];
	}

	/**
	 * @return array<int, int>
	 */
	private function getCommercialRoutesDiplomaticData(Color $faction, CommercialRouteManager $commercialRouteManager): array
	{
		return array_reduce(
			array_filter(array_keys($faction->getColorLink()), fn ($factionId) => !in_array($factionId, [0, $faction->getId()])),
			function ($acc, $factionId) use ($faction, $commercialRouteManager) {
				$acc[$factionId] = $commercialRouteManager->countCommercialRoutesBetweenFactions($faction->getId(), $factionId);

				return $acc;
			},
			[],
		);
	}
}
