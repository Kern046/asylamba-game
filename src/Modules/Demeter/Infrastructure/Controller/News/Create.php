<?php

namespace App\Modules\Demeter\Infrastructure\Controller\News;

use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Demeter\Model\Forum\FactionNews;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class Create extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		FactionNewsManager $factionNewsManager,
	): Response {
		$content = $request->request->get('content') ?? throw new BadRequestHttpException('Missing content');
		$title = $request->request->get('title') ?? throw new BadRequestHttpException('Missing title');

		if (!$currentPlayer->isGovernmentMember()) {
			throw $this->createAccessDeniedException('Vous n\'avez pas le droit de créer une annonce.');
		}

		$news = new FactionNews(
			id: Uuid::v4(),
			faction: $currentPlayer->faction,
			title: $title,
			oContent: '',
			pContent: '',
			pinned: false, // TODO Don't know what is it for
			statement: 0,
			createdAt: new \DateTimeImmutable(),
		);
		// TODO replace with a handler to retrieve formatted content
		$factionNewsManager->edit($news, $content);

		return $this->redirect($request->headers->get('referer'));
	}
}
