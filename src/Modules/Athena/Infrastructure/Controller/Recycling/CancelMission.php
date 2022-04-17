<?php

namespace App\Modules\Athena\Infrastructure\Controller\Recycling;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\RecyclingMissionManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\RecyclingMission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CancelMission extends AbstractController
{
	public function __invoke(
		Request $request,
		OrbitalBase $currentBase,
		OrbitalBaseManager $orbitalBaseManager,
		RecyclingMissionManager $recyclingMissionManager,
		EntityManager $entityManager,
		int $id,
	): Response {
		if (($mission = $recyclingMissionManager->get($id)) !== null && $mission->isActive()) {
			$mission->statement = RecyclingMission::ST_BEING_DELETED;

			$this->addFlash('success', 'Ordre de mission annulé.');

			$entityManager->flush($mission);

			return $this->redirect($request->headers->get('referer'));
		} else {
			throw new ErrorException('impossible de supprimer la mission.');
		}
	}
}
