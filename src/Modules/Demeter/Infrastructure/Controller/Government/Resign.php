<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Resign extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		EntityManager $entityManager,
	): Response {
		if ($currentPlayer->isGovernmentMember() && !$currentPlayer->isRuler()) {
			$currentPlayer->status = Player::PARLIAMENT;
			$request->getSession()->get('playerInfo')->add('status', Player::PARLIAMENT);
			$this->addFlash('success', 'Vous n\'êtes plus membre du gouvernement.');
			$entityManager->flush();

			return $this->redirect($request->headers->get('referer'));
		} else {
			throw new ErrorException('Vous n\'êtes pas dans le gouvernement de votre faction ou en êtes le chef.');
		}
	}
}
