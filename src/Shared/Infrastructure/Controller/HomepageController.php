<?php

namespace App\Shared\Infrastructure\Controller;

use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsActivePlayer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class HomepageController extends AbstractController
{
	public function __invoke(PlayerRepositoryInterface $playerRepository): Response
	{
		$players = $playerRepository->getBySpecification(new IsActivePlayer());

		return $this->render('pages/homepage.html.twig', [
			'active_players' => $players,
			'high_mode' => $this->getParameter('highmode'),
		]);
	}
}
