<?php

namespace App\Modules\Artemis\Infrastructure\Controller\SpyReport;

use App\Modules\Artemis\Domain\Repository\SpyReportRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class DeleteReport extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		SpyReportRepositoryInterface $spyReportRepository,
		Uuid $id,
	): Response {
		$spyReport = $spyReportRepository->get($id)
			?? throw $this->createNotFoundException('Ce rapport n\'existe pas');

		if ($currentPlayer->id !== $spyReport->player->id) {
			throw $this->createAccessDeniedException('Ce rapport ne vous appartient pas');
		}

		$spyReportRepository->remove($spyReport);

		$this->addFlash('success', 'Rapport d\'espionnage supprimé');

		return $this->redirectToRoute('spy_reports');
	}
}
