<?php

namespace App\Modules\Ares\Infrastructure\Controller\CombatReport;

use App\Modules\Ares\Domain\Repository\ReportRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DeleteAllReports extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		ReportRepositoryInterface $reportRepository,
	): Response {
		$reportRepository->removePlayerReports($currentPlayer);

		$this->addFlash('success', 'Vos rapports ont été correctement supprimés');

		return $this->redirectToRoute('fleet_archives');
	}
}
