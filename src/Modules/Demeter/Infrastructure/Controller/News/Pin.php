<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller\News;

use App\Modules\Demeter\Domain\Repository\Forum\FactionNewsRepositoryInterface;
use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class Pin extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		EntityManagerInterface $entityManager,
		FactionNewsManager $factionNewsManager,
		FactionNewsRepositoryInterface $factionNewsRepository,
		Uuid $id,
	): Response {
		$factionNews = $factionNewsRepository->getFactionNews($currentPlayer->faction);
		$newExists = false;
		// This way of doing things remove all previous pins
		foreach ($factionNews as $factionNew) {
			if ($factionNew->id->equals($id)) {
				$newExists = true;
				$factionNew->pinned = true;
			} else {
				$factionNew->pinned = false;
			}
		}
		if (true !== $newExists) {
			throw new NotFoundHttpException('Cette annonce n\'existe pas.');
		}

		$entityManager->flush();

		return $this->redirect($request->headers->get('referer'));
	}
}
