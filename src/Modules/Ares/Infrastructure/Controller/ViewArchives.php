<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Classes\Container\Params;
use App\Modules\Ares\Domain\Repository\LiveReportRepositoryInterface;
use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Model\Report;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class ViewArchives extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ReportRepositoryInterface $reportRepository,
		LiveReportRepositoryInterface $liveReportRepository,
		PlayerRepositoryInterface $playerRepository,
	): Response {
		$archived = ('archived' === $request->query->get('mode')) ? Report::ARCHIVED : Report::STANDARD;

		$rebels = (bool) $request->cookies->get('p'.Params::SHOW_REBEL_REPORT, Params::$params[Params::SHOW_REBEL_REPORT]);

		$combatReports = ($request->cookies->get('p'.Params::SHOW_ATTACK_REPORT, Params::$params[Params::SHOW_ATTACK_REPORT]))
			? $liveReportRepository->getAttackReportsByMode($currentPlayer, $rebels, $archived)
			: $liveReportRepository->getDefenseReportsByMode($currentPlayer, $rebels, $archived)
		;

		if (null !== ($reportId = $request->query->get('report'))) {
			if (!Uuid::isValid($reportId)) {
				throw new BadRequestHttpException('Invalid report ID');
			}

			$report = $reportRepository->get(Uuid::fromString($reportId));

			// TODO Voter
			if (!\in_array($currentPlayer->id, [$report->attacker->id, $report->defender?->id])) {
				throw $this->createAccessDeniedException('You cannot access this report');
			}
		}

		return $this->render('pages/ares/fleet/archives.html.twig', [
			'combat_reports' => $combatReports,
			'default_parameters' => Params::$params,
			'report' => $report ?? null,
			'report_attacker' => $report->attacker ?? null,
			'report_defender' => $report->defender ?? null,
		]);
	}
}
