<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Resign extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PlayerRepositoryInterface $playerRepository,
	): Response {
		if (!$currentPlayer->isGovernmentMember()) {
			throw new ConflictHttpException('Vous n\'êtes pas dans le gouvernement de votre faction.');
		}

		if ($currentPlayer->isRuler()) {
			throw new ConflictHttpException('Vous êtes le chef de votre faction.');
		}
		$currentPlayer->status = Player::PARLIAMENT;

		$request->getSession()->get('playerInfo')->add('status', Player::PARLIAMENT);

		$this->addFlash('success', 'Vous n\'êtes plus membre du gouvernement.');

		$playerRepository->save($currentPlayer);

		return $this->redirect($request->headers->get('referer'));
	}
}
