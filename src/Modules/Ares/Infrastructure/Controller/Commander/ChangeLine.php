<?php

namespace App\Modules\Ares\Infrastructure\Controller\Commander;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Gaia\Resource\PlaceResource;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChangeLine extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CommanderManager $commanderManager,
		OrbitalBaseManager $orbitalBaseManager,
		EntityManager $entityManager,
		int $id,
	): Response {
		if (($commander = $commanderManager->get($id)) === null || $commander->rPlayer !== $currentPlayer->getId()) {
			throw new ErrorException('Ce commandant n\'existe pas ou ne vous appartient pas');
		}
		$orbitalBase = $orbitalBaseManager->get($commander->rBase);

		# checker si on a assez de place !!!!!
		if ($commander->line == 1) {
			$secondLineCommanders = $commanderManager->getCommandersByLine($commander->rBase, 2);

			$commander->line = 2;
			if (count($secondLineCommanders) >= PlaceResource::get($orbitalBase->typeOfBase, 'r-line')) {
				$secondLineCommanders[0]->line = 1;

				$this->addFlash('success', 'Votre commandant ' . $commander->getName() . ' a échangé sa place avec ' . $commander->name . '.');
			}
		} else {
			$firstLineCommanders = $commanderManager->getCommandersByLine($commander->rBase, 1);

			# tutorial
			if ($currentPlayer->stepDone !== true && $currentPlayer->getStepTutorial() === TutorialResource::MOVE_FLEET_LINE) {
				$tutorialHelper->setStepDone($currentPlayer);
			}

			$commander->line = 1;
			if (count($firstLineCommanders) >= PlaceResource::get($orbitalBase->typeOfBase, 'l-line')) {
				$firstLineCommanders[0]->line = 2;
				$this->addFlash('success', 'Votre commandant ' . $commander->getName() . ' a échangé sa place avec ' . $commander->name . '.');
			}
		}
		$entityManager->flush();

		return $this->redirect($request->headers->get('referer'));
	}
}
