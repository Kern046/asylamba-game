<?php

namespace App\Modules\Demeter\Infrastructure\Controller\News;

use App\Modules\Demeter\Domain\Repository\Forum\FactionNewsRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class Delete extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		FactionNewsRepositoryInterface $factionNewsRepository,
		Uuid $id,
	): Response {
		if (($factionNew = $factionNewsRepository->get($id)) === null) {
			throw $this->createNotFoundException('Cette annonce n\'existe pas.');
		}
		// TODO replace with voter
		if (!$currentPlayer->isGovernmentMember() || $currentPlayer->faction->id !== $factionNew->faction->id) {
			throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer cette annonce');
		}

		$factionNewsRepository->remove($factionNew);

		$this->addFlash('success', 'L\'annonce a bien été supprimée.');

		return $this->redirect($request->headers->get('referer'));
	}
}
