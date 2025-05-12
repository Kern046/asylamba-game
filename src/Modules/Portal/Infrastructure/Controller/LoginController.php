<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
	#[Route(
		path: '/login',
		name: 'login',
		methods: ['GET']
	)]
	public function __invoke()
	{
		return $this->render('pages/portal/login.html.twig');
	}
}
