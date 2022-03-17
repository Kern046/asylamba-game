<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\FormException;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateDescription extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ColorManager $colorManager,
		EntityManager $entityManager,
	): Response {
		if (($description = $request->request->get('description')) !== FALSE) {
			if ($currentPlayer->isGovernmentMember()) {
				if ($description !== '' && strlen($description) < 25000) {
					$faction = $colorManager->get($currentPlayer->getRColor());
					$faction->description = $description;

					$entityManager->flush($faction);

					return $this->redirect($request->headers->get('referer'));
				} else {
					throw new FormException('La description est vide ou trop longue');
				}
			} else {
				throw new FormException('Vous n\'avez pas les droits pour poster une description');
			}
		} else {
			throw new FormException('Pas assez d\'informations pour écrire une description');
		}
	}
}
