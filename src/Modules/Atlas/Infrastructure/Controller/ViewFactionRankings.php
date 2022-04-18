<?php

namespace App\Modules\Atlas\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewFactionRankings extends AbstractController
{
	public function __invoke(): Response
	{
		return $this->render('pages/atlas/faction_rankings.html.twig', [

		]);
	}
}
