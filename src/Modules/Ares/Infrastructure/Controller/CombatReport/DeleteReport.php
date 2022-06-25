<?php

namespace App\Modules\Ares\Infrastructure\Controller\CombatReport;

use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Ares\Model\Report;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class DeleteReport extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ReportRepositoryInterface $reportRepository,
		Uuid $id,
	): Response {
		if (($report = $reportRepository->get($id)) !== null) {
			if ($report->rPlayerAttacker == $currentPlayer->id) {
				$report->statementAttacker = Report::DELETED;
			} elseif ($report->rPlayerDefender == $currentPlayer->id) {
				$report->statementDefender = Report::DELETED;
			} else {
				throw new AccessDeniedHttpException('Ce rapport ne vous appartient pas');
			}
		} else {
			throw new NotFoundHttpException('Ce rapport n\'existe pas');
		}
		$reportRepository->save($report);

		return $this->redirectToRoute('fleet_archives');
	}
}
