<?php

namespace App\Modules\Demeter\Infrastructure\Controller\News;

use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Utils;
use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Demeter\Model\Forum\FactionNews;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Create extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		FactionNewsManager $factionNewsManager,
	): Response {
		$content = $request->request->get('content');
		$title = $request->request->get('title');

		if ($title !== null AND $content !== null) {
			if ($currentPlayer->isGovernmentMember()) {
				$news = new FactionNews();
				$news->rFaction = $currentPlayer->getRColor();
				$news->title = $title;
				$factionNewsManager->edit($news, $content);
				$news->dCreation = Utils::now();

				$factionNewsManager->add($news);

				return $this->redirect($request->headers->get('referer'));
			} else {
				throw new ErrorException('Vous n\'avez pas le droit de créer une annonce.');
			}
		} else {
			throw new FormException('Manque d\'information.');
		}
	}
}
