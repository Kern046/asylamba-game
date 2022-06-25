<?php

namespace App\Modules\Ares\Infrastructure\Controller\CombatReport;

use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Model\Report;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class ArchiveReport extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		ReportRepositoryInterface $reportRepository,
		Uuid $id
	): Response {
		if (($report = $reportRepository->get($id)) !== null) {
			if ($currentPlayer->id === $report->rPlayerAttacker) {
				if (Report::STANDARD === $report->statementAttacker) {
					$report->statementAttacker = Report::ARCHIVED;
				} else {
					$report->statementAttacker = Report::STANDARD;
				}
			} elseif ($currentPlayer->id === $report->rPlayerDefender) {
				if (Report::STANDARD == $report->statementDefender) {
					$report->statementDefender = Report::ARCHIVED;
				} else {
					$report->statementDefender = Report::STANDARD;
				}
			} else {
				throw new AccessDeniedHttpException('Ce rapport ne vous appartient pas.');
			}
		} else {
			throw new NotFoundHttpException('Ce rapport n\'existe pas.');
		}
		$reportRepository->save($report);

		return $this->redirectToRoute('fleet_archives');
	}
}
