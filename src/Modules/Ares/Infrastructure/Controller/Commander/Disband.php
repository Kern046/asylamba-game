<?php

namespace App\Modules\Ares\Infrastructure\Controller\Commander;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Disband extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		Uuid $id,
	): Response {
		$commander = $commanderRepository->get($id)
			?? throw $this->createNotFoundException('Ce commandant n\'existe pas.');
		// TODO Voter
		if ($commander->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('Ce commandant ne vous appartient pas');
		}
		if (!$commander->isAffected()) {
			throw new ConflictHttpException('Vous ne pouvez pas retirer les vaisseaux à un officier en déplacement.');
		}

		// vider le commandant
		$commanderManager->emptySquadrons($commander);

		$this->addFlash('success', 'Vous avez vidé l\'armée menée par votre commandant '.$commander->name.'.');

		$commanderRepository->save($commander);

		return $this->redirect($request->headers->get('referer'));
	}
}
