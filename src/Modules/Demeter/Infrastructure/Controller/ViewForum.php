<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewForum extends AbstractController
{
	public function __invoke(
		Request $request,
	): Response {
		return $this->render('pages/demeter/forum.html.twig');
	}
}
