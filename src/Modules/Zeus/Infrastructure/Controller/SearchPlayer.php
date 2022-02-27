<?php

namespace App\Modules\Zeus\Infrastructure\Controller;

use App\Classes\Exception\ErrorException;
use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchPlayer extends AbstractController
{
	public function __invoke(
		Request $request,
		PlayerManager $playerManager,
	): Response {
		$id = $request->request->get('playerid');
		if (($player = $playerManager->get($id)) !== null) {
			return $this->redirectToRoute('embassy', ['player' => $player->getId()]);
		} else {
			throw new ErrorException('Aucun joueur ne correspond à votre recherche.');
		}
	}
}
