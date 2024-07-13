<?php

namespace App\Modules\Athena\Infrastructure\Controller\Recycling;

use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class AddToMission extends AbstractController
{
	public function __invoke(
		Request $request,
		OrbitalBase $currentBase,
		OrbitalBaseHelper $orbitalBaseHelper,
		RecyclingMissionRepositoryInterface $recyclingMissionRepository,
		Uuid $id,
	): Response {
		$quantity = $request->request->getInt('quantity');

		if (0 === $quantity) {
			throw new BadRequestHttpException('Ca va être dur de recycler avec aussi peu de recycleurs. Entrez un nombre plus grand que zéro.');
		}
		$maxRecyclers = $orbitalBaseHelper->getInfo(OrbitalBaseResource::RECYCLING, 'level', $currentBase->levelRecycling, 'nbRecyclers');
		$usedRecyclers = 0;

		$baseMissions = $recyclingMissionRepository->getBaseActiveMissions($currentBase);

		$mission = null;
		foreach ($baseMissions as $baseMission) {
			$usedRecyclers += $baseMission->recyclerQuantity + $baseMission->addToNextMission;
			if ($baseMission->id->equals($id) && $baseMission->isActive()) {
				$mission = $baseMission;
			}
		}

		if (null === $mission) {
			throw $this->createNotFoundException('Il y a un problème, la mission est introuvable. Veuillez contacter un administrateur.');
		}

		if ($maxRecyclers - $usedRecyclers < $quantity) {
			throw new ConflictHttpException('Vous n\'avez pas assez de recycleurs libres pour lancer cette mission.');
		}

		$mission->addToNextMission += $quantity;

		$recyclingMissionRepository->save($mission);

		$this->addFlash('success', 'Vos recycleurs ont bien été affectés, ils seront ajoutés à la prochaine mission.');

		return $this->redirect($request->headers->get('referer'));
	}
}
