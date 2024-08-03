<?php

namespace App\Modules\Demeter\Infrastructure\Controller\News;

use App\Modules\Demeter\Domain\Repository\Forum\FactionNewsRepositoryInterface;
use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class Edit extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		FactionNewsManager $factionNewsManager,
		FactionNewsRepositoryInterface $factionNewsRepository,
		Uuid $id,
	): Response {
		$content = $request->request->get('content') ?? throw new BadRequestHttpException('Missing content');
		$title = $request->request->get('title') ?? throw new BadRequestHttpException('Missing title');

		$factionNew = $factionNewsRepository->get($id)
			?? throw $this->createNotFoundException('Faction news not found');

		// TODO Replace with voter
		if (!$currentPlayer->isGovernmentMember() || !$currentPlayer->faction->id->equals($factionNew->faction->id)) {
			throw $this->createAccessDeniedException('Vous n\'avez pas le droit pour créer une annonce.');
		}

		$factionNew->title = $title;
		$factionNewsManager->edit($factionNew, $content);

		return $this->redirect($request->headers->get('referer'));
	}
}
