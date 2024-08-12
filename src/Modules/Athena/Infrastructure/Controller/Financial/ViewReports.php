<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Controller\Financial;

use App\Classes\Library\Chronos;
use App\Modules\Zeus\Domain\Repository\PlayerFinancialReportRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewReports extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		PlayerFinancialReportRepositoryInterface $playerFinancialReportRepository,
	): Response {
		$playerFinancialReports = $playerFinancialReportRepository->getPlayerLastReports($currentPlayer);

		return $this->render('pages/athena/financial/reports.html.twig', [
			'reports_labels' => array_map(
				fn (PlayerFinancialReport $playerFinancialReport) => Chronos::transform($playerFinancialReport->createdAt, false),
				$playerFinancialReports,
			),
			'reports_data' => array_reduce(
				$playerFinancialReports,
				function (array $carry, PlayerFinancialReport $playerFinancialReport) {
					$carry['populationTaxes'][] = $playerFinancialReport->populationTaxes;
					$carry['commercialRoutesIncome'][] = $playerFinancialReport->commercialRoutesIncome;
					$carry['factionTaxes'][] = -$playerFinancialReport->factionTaxes;
					$carry['antiSpyInvestments'][] = -$playerFinancialReport->antiSpyInvestments;
					$carry['universityInvestments'][] = -$playerFinancialReport->universityInvestments;
					$carry['schoolInvestments'][] = -$playerFinancialReport->schoolInvestments;
					$carry['commandersWages'][] = -$playerFinancialReport->commandersWages;
					$carry['shipsCost'][] = -$playerFinancialReport->shipsCost;
					$carry['diff'][] = $playerFinancialReport->getDiff();

					return $carry;
				},
				[
					'populationTaxes' => [],
					'commercialRoutesIncome' => [],
					'factionTaxes' => [],
					'antiSpyInvestments' => [],
					'universityInvestments' => [],
					'schoolInvestments' => [],
					'commandersWages' => [],
					'shipsCost' => [],
					'diff' => [],
				]
			)
		]);
	}
}
