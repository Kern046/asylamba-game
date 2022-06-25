<?php

namespace App\Modules\Zeus\Infrastructure\Controller;

use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class SearchPlayer extends AbstractController
{
	public function __invoke(
		Request $request,
		PlayerRepositoryInterface $playerRepository,
	): Response {
		if (!Uuid::isValid($id = $request->request->get('playerid'))) {
			throw new BadRequestHttpException('Invalid ID provided');
		}

		if (($player = $playerRepository->get(Uuid::fromString($id))) === null) {
			throw $this->createNotFoundException('Aucun joueur ne correspond à votre recherche.');
		}

		return $this->redirectToRoute('embassy', ['player' => $player->id]);
	}
}
