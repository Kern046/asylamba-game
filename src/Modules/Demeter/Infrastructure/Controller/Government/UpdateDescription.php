<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateDescription extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ColorRepositoryInterface $colorRepository,
	): Response {
		$description = $request->request->get('description')
			?? throw new BadRequestHttpException('Missing description');

		// TODO Replace with a voter
		if (!$currentPlayer->isGovernmentMember()) {
			throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour poster une description');
		}
		// TODO Replace with validation component
		if ('' === $description || strlen($description) > 25000) {
			throw new BadRequestHttpException('La description est vide ou trop longue');
		}
		$faction = $currentPlayer->faction;
		$faction->description = $description;

		$colorRepository->save($faction);

		return $this->redirect($request->headers->get('referer'));
	}
}
