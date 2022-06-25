<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Gaia\Resource\PlaceResource;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class PutCommanderInSchool extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CommanderRepositoryInterface $commanderRepository,
		Uuid $id
	): Response {
		// TODO Replace with Voter
		if (null === ($commander = $commanderRepository->get($id)) || $commander->player->id !== $currentPlayer->id) {
			throw new BadRequestHttpException('Ce commandant n\'existe pas ou ne vous appartient pas');
		}
		$orbitalBase = $commander->base;

		if (Commander::RESERVE == $commander->statement) {
			$commanders = $commanderRepository->getBaseCommanders($commander->base, [Commander::INSCHOOL]);

			if (count($commanders) < PlaceResource::get($orbitalBase->typeOfBase, 'school-size')) {
				$commander->statement = Commander::INSCHOOL;
				$commander->updatedAt = new \DateTimeImmutable();
			} else {
				throw new ConflictHttpException('Votre école est déjà pleine.');
			}
		} elseif (Commander::INSCHOOL == $commander->statement) {
			$commander->statement = Commander::RESERVE;
			$commander->updatedAt = new \DateTimeImmutable();
		} else {
			throw new ConflictHttpException('Vous ne pouvez rien faire avec cet officier.');
		}
		$commanderRepository->save($commander);

		return $this->redirectToRoute('school');
	}
}
