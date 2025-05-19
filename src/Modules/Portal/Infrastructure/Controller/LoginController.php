<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
	#[Route(
		path: '/login',
		name: 'login',
		methods: ['GET', 'POST']
	)]
	public function __invoke(AuthenticationUtils $authenticationUtils): Response
	{
		$error = $authenticationUtils->getLastAuthenticationError();
		// last username entered by the user
		$lastUsername = $authenticationUtils->getLastUsername();

		return $this->render('pages/portal/login.html.twig', [
			'error' => $error,
			'last_username' => $lastUsername,
		]);
	}
}
