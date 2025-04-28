<?php

namespace App\Modules\Zeus\Infrastructure\Controller;

use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		CommercialRouteManager $commercialRouteManager,
		EntityManagerInterface $entityManager,
	): Response {
		return $this->render('pages/zeus/profile.html.twig', [
			'player_bases' => $orbitalBaseRepository->getPlayerBases($currentPlayer),
			'has_splash_mode' => 'splash' === $request->query->get('mode'),
			'building_resource_refund' => $this->getParameter('athena.building.building_queue_resource_refund'),
		]);
	}
}
