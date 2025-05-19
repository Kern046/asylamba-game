<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Controller;

use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsActivePlayer;
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
		$players = $playerRepository->getBySpecification(new IsActivePlayer());

		return $this->render('pages/portal/player_choice.html.twig', [
			'active_players' => $players,
			'high_mode' => $this->getParameter('highmode'),
		]);
	}
}
