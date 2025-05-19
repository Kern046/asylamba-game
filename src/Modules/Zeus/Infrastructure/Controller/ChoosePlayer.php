<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Controller;

use App\Modules\Portal\Domain\Entity\User;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\DoesPlayerBelongTo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChoosePlayer extends AbstractController
{
	#[Route(
		path: '/player-choice',
		name: 'player_choice',
		methods: ['GET'],
	)]
	public function __invoke(PlayerRepositoryInterface $playerRepository): Response
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

		/** @var User $user */
		$user = $this->getUser();
		$players = $playerRepository->getBySpecification(new DoesPlayerBelongTo($user));

		return $this->render('pages/portal/player_choice.html.twig', [
			'active_players' => $players,
			'high_mode' => $this->getParameter('highmode'),
		]);
	}
}
