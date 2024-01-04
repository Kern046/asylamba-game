<?php

namespace App\Modules\Athena\Infrastructure\Controller\Recycling;

use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class CancelMission extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		RecyclingMissionRepositoryInterface $recyclingMissionRepository,
		Uuid $id,
	): Response {
		$mission = $recyclingMissionRepository->get($id)
			?? throw $this->createNotFoundException('Mission not found');

		if ($mission->base->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('This mission does not belong to you');
		}

		if (!$mission->isActive()) {
			throw new ConflictHttpException('This mission is not active');
		}

		$mission->cancel();

		$this->addFlash('success', 'Ordre de mission annulé.');

		$recyclingMissionRepository->save($mission);

		return $this->redirect($request->headers->get('referer'));
	}
}
