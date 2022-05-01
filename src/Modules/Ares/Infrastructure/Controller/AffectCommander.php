<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Classes\Entity\EntityManager;
use App\Classes\Library\Utils;
use App\Modules\Ares\Domain\Event\Commander\AffectationEvent;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Gaia\Resource\PlaceResource;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AffectCommander extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		CommanderManager $commanderManager,
		OrbitalBaseManager $orbitalBaseManager,
		OrbitalBaseHelper $orbitalBaseHelper,
		EventDispatcherInterface $eventDispatcher,
		EntityManager $entityManager,
		int $id,
	): Response {
		if (($commander = $commanderManager->get($id)) === null) {
			throw new \ErrorException('Cet officier n\'existe pas ou ne vous appartient pas');
		}

		$orbitalBase = $orbitalBaseManager->get($commander->rBase);

		// checker si on a assez de place !!!!!
		$nbrLine1 = $commanderManager->countCommandersByLine($commander->rBase, 1);
		$nbrLine2 = $commanderManager->countCommandersByLine($commander->rBase, 2);

		if (Commander::INSCHOOL == $commander->statement || Commander::RESERVE == $commander->statement) {
			if ($nbrLine2 < PlaceResource::get($orbitalBase->typeOfBase, 'r-line')) {
				$commander->line = 2;
				$statement = 'de réserve';
			} elseif ($nbrLine1 < PlaceResource::get($orbitalBase->typeOfBase, 'l-line')) {
				$commander->line = 1;
				$statement = 'active';
			} else {
				throw new \ErrorException('Votre base a dépassé la capacité limite de officiers en activité');
			}
			$commander->dAffectation = Utils::now();
			$commander->statement = Commander::AFFECTED;

			$entityManager->flush();

			$eventDispatcher->dispatch(new AffectationEvent($commander, $currentPlayer));

			$this->addFlash('success', sprintf('Votre officier %s a bien été affecté en force %s', $commander->getName(), $statement));

			return $this->redirectToRoute('fleet_headquarters', ['commander' => $commander->getId()]);
		} elseif (Commander::AFFECTED == $commander->statement) {
			$baseCommanders = $commanderManager->getBaseCommanders($commander->rBase, [Commander::INSCHOOL]);

			$commander->uCommander = Utils::now();
			if (count($baseCommanders) < PlaceResource::get($orbitalBase->typeOfBase, 'school-size')) {
				$commander->statement = Commander::INSCHOOL;
				$this->addFlash('success', 'Votre officier '.$commander->getName().' a été remis à l\'école');
				$commanderManager->emptySquadrons($commander);
			} else {
				$commander->statement = Commander::RESERVE;
				$this->addFlash('success', 'Votre officier '.$commander->getName().' a été remis dans la réserve de l\'armée');
				$commanderManager->emptySquadrons($commander);
			}
			$entityManager->flush();

			return $this->redirectToRoute('school');
		} else {
			throw new ConflictHttpException('Le status de votre officier ne peut pas être modifié');
		}
	}
}
