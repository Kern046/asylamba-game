<?php

namespace App\Modules\Ares\Infrastructure\Controller\Commander;

use App\Classes\Exception\ErrorException;
use App\Classes\Library\Parser;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateName extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CommanderManager $commanderManager,
		Parser $parser,
		int $id,
	): Response {
		if (($commander = $commanderManager->get($id)) === null || $commander->rPlayer !== $currentPlayer->getId()) {
			throw new ErrorException('Ce commandant n\'existe pas ou ne vous appartient pas');
		}
		$name = $parser->protect($request->request->get('name'));
		if (strlen($name) > 1 AND strlen($name) < 26) {
			$commander->setName($name);
			$this->addFlash('success', 'le nom de votre commandant est maintenant ' . $name);

			return $this->redirect($request->headers->get('referer'));
		} else {
			throw new ErrorException('le nom doit comporter entre 2 et 25 caractères');
		}
	}
}
