<?php

namespace App\Modules\Athena\Infrastructure\Controller\Ship;

use App\Classes\Library\Format;
use App\Modules\Athena\Application\Factory\ShipQueueFactory;
use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\Ship\CountShipResourceCost;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Helper\ShipHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class BuildShips extends AbstractController
{
	public function __invoke(
		Request                       $request,
		Player                        $currentPlayer,
		OrbitalBase                   $currentBase,
		OrbitalBaseManager            $orbitalBaseManager,
		OrbitalBaseHelper             $orbitalBaseHelper,
		CountShipResourceCost         $countShipResourceCost,
		ShipHelper                    $shipHelper,
		ShipQueueRepositoryInterface  $shipQueueRepository,
		ShipQueueFactory              $shipQueueFactory,
		TechnologyRepositoryInterface $technologyRepository,
	): Response {
		$session = $request->getSession();
		$shipIdentifier = $request->query->getInt('ship')
			?? throw new BadRequestHttpException('Missing ship identifier');
		$quantity = $request->query->getInt('quantity')
			?? throw new BadRequestHttpException('Missing quantity');

		if (0 === $quantity) {
			throw new BadRequestHttpException('Quantity must be higher than 0');
		}
		if (!ShipResource::isAShip($shipIdentifier)) {
			throw new BadRequestHttpException('Invalid ship identifier');
		}
		$dockType = DockType::fromShipIdentifier($shipIdentifier);
		if (DockType::Shipyard === $dockType) {
			$quantity = 1;
		}
		$shipQueues = $shipQueueRepository->getByBaseAndDockType($currentBase, $dockType->getIdentifier());
		$shipQueuesCount = count($shipQueues);
		$technos = $technologyRepository->getPlayerTechnology($currentPlayer);
		// TODO Replace with Specification pattern
		if (
			!$shipHelper->haveRights($shipIdentifier, 'resource', $currentBase->resourcesStorage, $quantity)
			|| !$shipHelper->haveRights($shipIdentifier, 'queue', $currentBase, $shipQueuesCount)
			|| !$shipHelper->haveRights($shipIdentifier, 'shipTree', $currentBase)
			|| !$shipHelper->haveRights($shipIdentifier, 'pev', $currentBase, $quantity)
			|| !$shipHelper->haveRights($shipIdentifier, 'techno', $technos)
		) {
			throw new ConflictHttpException('Missing some conditions to launch the build order');
		}
		// TODO create a dedicated service for queued durations
		$startedAt = (0 === $shipQueuesCount)
			? new \DateTimeImmutable()
			: $shipQueues[$shipQueuesCount - 1]->getEndDate();

		$shipQueue = $shipQueueFactory->create(
			orbitalBase: $currentBase,
			shipIdentifier: $shipIdentifier,
			dockType: $dockType,
			quantity: $quantity,
			startedAt: $startedAt,
		);

		// débit des ressources au joueur
		$resourcePrice = ($countShipResourceCost)($shipIdentifier, $quantity, $currentPlayer);
		$orbitalBaseManager->decreaseResources($currentBase, $resourcePrice);

		// ajout de l'event dans le contrôleur
		$session->get('playerEvent')->add($shipQueue->getEndDate(), $this->getParameter('event_base'), $currentBase->id);

		//						if (true === $this->getContainer()->getParameter('data_analysis')) {
		//							$qr = $database->prepare('INSERT INTO
		//						DA_BaseAction(`from`, type, opt1, opt2, weight, dAction)
		//						VALUES(?, ?, ?, ?, ?, ?)'
		//							);
		//							$qr->execute([$session->get('playerId'), 3, $ship, $quantity, DataAnalysis::resourceToStdUnit(ShipResource::getInfo($ship, 'resourcePrice') * $quantity), Utils::now()]);
		//						}

		// alerte
		if (1 == $quantity) {
			$this->addFlash('success', 'Construction d\'' . (ShipResource::isAFemaleShipName($shipIdentifier) ? 'une ' : 'un ') . ShipResource::getInfo($shipIdentifier, 'codeName') . ' commandée');
		} else {
			$this->addFlash('success', 'Construction de ' . $quantity . ' ' . ShipResource::getInfo($shipIdentifier, 'codeName') . Format::addPlural($quantity) . ' commandée');
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
