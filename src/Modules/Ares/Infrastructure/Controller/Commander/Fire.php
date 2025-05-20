<?php

namespace App\Modules\Ares\Infrastructure\Controller\Commander;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class Fire extends AbstractController
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

		if ($commander->isAffected() || $commander->isInSchool() || $commander->isInReserve()) {
			// vider le commandant
			$commanderManager->emptySquadrons($commander);
			$commander->statement = Commander::DESERT;

			$commanderRepository->save($commander);

			$this->addFlash('success', 'Vous avez renvoyé votre commandant '.$commander->name.'.');
		} else {
			$this->addFlash('error', 'Vous ne pouvez pas renvoyer un officier en déplacement.');
		}

		return $this->redirectToRoute('fleet_headquarters');
	}
}
