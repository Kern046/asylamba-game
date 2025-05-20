<?php

namespace App\Modules\Portal\Infrastructure\Controller;

use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsActivePlayer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomepageController extends AbstractController
{
	#[Route(
		path: '/',
		name: 'homepage',
		methods: ['GET'],
	)]
	public function __invoke(PlayerRepositoryInterface $playerRepository): Response
	{
		$players = $playerRepository->getBySpecification(new IsActivePlayer());

		return $this->render('pages/portal/homepage.html.twig');
	}
}
