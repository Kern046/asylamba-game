<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\Format;
use App\Classes\Redis\RedisManager;
use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Resource\CommanderResources;
use App\Modules\Athena\Domain\Repository\CommercialTaxRepositoryInterface;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Atlas\Domain\Repository\FactionRankingRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Gaia\Galaxy\GalaxyConfiguration;
use App\Modules\Gaia\Manager\SectorManager;
use App\Modules\Zeus\Domain\Repository\CreditTransactionRepositoryInterface;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// @TODO split this controller into multiple ones
class ViewData extends AbstractController
{
	public function __invoke(
		Request                              $request,
		Player                               $currentPlayer,
		ColorManager                         $colorManager,
		ColorRepositoryInterface             $colorRepository,
		CommanderManager                     $commanderManager,
		CommercialRouteManager               $commercialRouteManager,
		CommercialTaxRepositoryInterface     $commercialTaxRepository,
		CreditTransactionRepositoryInterface $creditTransactionRepository,
		ReportRepositoryInterface            $reportRepository,
		FactionRankingRepositoryInterface    $factionRankingRepository,
		GalaxyConfiguration                  $galaxyConfiguration,
		SectorManager                        $sectorManager,
		RedisManager                         $redisManager,
	): Response {
		$faction = $currentPlayer->getRColor();

		$factionRankingRepository->loadByRequest(
			'WHERE rFaction = ? ORDER BY rRanking DESC LIMIT 0, 20',
			[$faction->id]
		);

		$creditBase = 0;
		for ($i = 0; $i < $factionRankingRepository->size(); ++$i) {
			if ($creditBase < $factionRankingRepository->get($i)->wealth) {
				$creditBase = $factionRankingRepository->get($i)->wealth;
			}
		}
		// @TODO factoriser cette formule
		$creditBase += $creditBase * 12 / 100;

		// load
		$creditTransactionRepository->newSession();
		$creditTransactionRepository->load(
			['rReceiver' => $faction->id, 'type' => CreditTransaction::TYP_FACTION],
			['dTransaction', 'DESC'],
			[0, 20],
		);
		$membersDonations = $creditTransactionRepository->getAll();

		$creditTransactionRepository->newSession();
		$creditTransactionRepository->load(
			['rSender' => $faction->id, 'type' => CreditTransaction::TYP_F_TO_P],
			['dTransaction', 'DESC'],
			[0, 20],
		);
		$factionDonations = $creditTransactionRepository->getAll();

		$commercialTaxRepository->newSession();
		$commercialTaxRepository->load(['faction' => $faction->id], ['importTax', 'ASC']);
		$importaxes = $commercialTaxRepository->getAll();

		$commercialTaxRepository->newSession();
		$commercialTaxRepository->load(['faction' => $faction->id], ['exportTax', 'ASC']);
		$exportTaxes = $commercialTaxRepository->getAll();

		$commanderStats = $commanderManager->getFactionCommanderStats($faction->getId());
		$fleetStats = $commanderManager->getFactionFleetStats($faction->getId());

		$totalPEV = 0;
		for ($i = 0; $i < 12; ++$i) {
			$totalPEV += $fleetStats['nbs'.$i] * ShipResource::getInfo($i, 'pev');
		}

		$factions = $colorRepository->findAll();
		$sectors = $sectorManager->getAll();
		$mapData = $this->getTacticalMapData($faction->getId(), $factions, $sectors, $redisManager);

		return $this->render('pages/demeter/faction/data.html.twig', [
			'faction' => $faction,
			'credit_base' => $creditBase,
			'faction_rankings' => $factionRankingRepository->getAll(),
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
			'attack_reports' => $reportRepository->getFactionAttackReports($faction->getId()),
			'defense_reports' => $reportRepository->getFactionDefenseReports($faction->getId()),
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
				Color::NEUTRAL => 'Neutre',
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
				$percents = ['color'.$factionId => 0];
				$scores = unserialize($redisManager->getConnection()->get('sector:'.$sector->getId()));

				if (!isset($scores[$factionId]) && $sector->getRColor() !== $factionId) {
					unset($sectors[$key]);
					continue;
				}
				if ('Secteurs conquis' === $type && $sector->getRColor() !== $factionId) {
					continue;
				}

				foreach ($factions as $f) {
					if (0 === $f->id || !isset($scores[$f->id])) {
						continue;
					}
					$percents['color'.$f->id] = round(Format::percent($scores[$f->id], array_sum($scores), false));
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
