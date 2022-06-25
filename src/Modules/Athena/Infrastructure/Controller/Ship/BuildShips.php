<?php

namespace App\Modules\Athena\Infrastructure\Controller\Ship;

use App\Classes\Library\Format;
use App\Classes\Library\Utils;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Helper\ShipHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\ShipQueueManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class BuildShips extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		DurationHandler $durationHandler,
		BonusApplierInterface $bonusApplier,
		OrbitalBase $currentBase,
		OrbitalBaseManager $orbitalBaseManager,
		OrbitalBaseHelper $orbitalBaseHelper,
		ShipQueueManager $shipQueueManager,
		ShipHelper $shipHelper,
		TechnologyManager $technologyManager,
		ShipQueueRepositoryInterface $shipQueueRepository,
		TechnologyRepositoryInterface $technologyRepository,
	): Response {
		$session = $request->getSession();
		$shipIdentifier = $request->query->getInt('ship') ?? throw new BadRequestHttpException('Missing ship identifier');
		$quantity = $request->query->getInt('quantity') ?? throw new BadRequestHttpException('Missing quantity');

		if (0 === $quantity) {
			throw new BadRequestHttpException('Quantity must be higher than 0');
		}
		if (!ShipResource::isAShip($shipIdentifier)) {
			throw new BadRequestHttpException('Invalid ship identifier');
		}
		if ($orbitalBaseHelper->isAShipFromDock1($shipIdentifier)) {
			$dockType = 1;
		} elseif ($orbitalBaseHelper->isAShipFromDock2($shipIdentifier)) {
			$dockType = 2;
			$quantity = 1;
		} else {
			$dockType = 3;
			$quantity = 1;
		}
		$shipQueues = $shipQueueRepository->getByBaseAndDockType($currentBase, $dockType);
		$shipQueuesCount = count($shipQueues);
		$technos = $technologyRepository->getPlayerTechnology($currentPlayer);
		// TODO Replace with Specification pattern
		if (!$shipHelper->haveRights($shipIdentifier, 'resource', $currentBase->resourcesStorage, $quantity)
			|| !$shipHelper->haveRights($shipIdentifier, 'queue', $currentBase, $shipQueuesCount)
			|| !$shipHelper->haveRights($shipIdentifier, 'shipTree', $currentBase)
			|| !$shipHelper->haveRights($shipIdentifier, 'pev', $currentBase, $quantity)
			|| !$shipHelper->haveRights($shipIdentifier, 'techno', $technos)) {
			throw new ConflictHttpException('Missing some conditions to launch the build order');
		}
		// construit le(s) nouveau(x) vaisseau(x)

		$time = ShipResource::getInfo($shipIdentifier, 'time') * $quantity;

		$bonus = $bonusApplier->apply($time, match ($dockType) {
			1 => PlayerBonusId::DOCK1_SPEED,
			2 => PlayerBonusId::DOCK2_SPEED,
			3 => PlayerBonusId::DOCK3_SPEED,
			default => throw new \LogicException('Invalid Dock ID'),
		});

		// TODO create a dedicated service for queued durations
		$startedAt = (0 === $shipQueuesCount)
			? new \DateTimeImmutable()
			: $shipQueues[$shipQueuesCount - 1]->getEndDate();
		$endedAt = $durationHandler->getDurationEnd($startedAt, round($time - $bonus));

		$sq = new ShipQueue(
			id: Uuid::v4(),
			base: $currentBase,
			startedAt: $startedAt,
			endedAt: $endedAt,
			dockType: $dockType,
			shipNumber: $shipIdentifier,
			quantity: $quantity,
		);

		$shipQueueManager->add($sq, $currentPlayer);

		// débit des ressources au joueur
		$resourcePrice = ShipResource::getInfo($shipIdentifier, 'resourcePrice') * $quantity;
		// TODO Refactor the way faction bonuses are retrieved and applied
		if (in_array($shipIdentifier, [ShipResource::CERBERE, ShipResource::PHENIX])) {
			if (in_array(ColorResource::PRICEBIGSHIPBONUS, ColorResource::getInfo($currentPlayer->faction->identifier, 'bonus'))) {
				$resourcePrice -= round($resourcePrice * ColorResource::BONUS_EMPIRE_CRUISER / 100);
			}
		}
		$orbitalBaseManager->decreaseResources($currentBase, $resourcePrice);

		// ajout de l'event dans le contrôleur
		$session->get('playerEvent')->add($sq->getEndDate(), $this->getParameter('event_base'), $currentBase->id);

		//						if (true === $this->getContainer()->getParameter('data_analysis')) {
		//							$qr = $database->prepare('INSERT INTO
		//						DA_BaseAction(`from`, type, opt1, opt2, weight, dAction)
		//						VALUES(?, ?, ?, ?, ?, ?)'
		//							);
		//							$qr->execute([$session->get('playerId'), 3, $ship, $quantity, DataAnalysis::resourceToStdUnit(ShipResource::getInfo($ship, 'resourcePrice') * $quantity), Utils::now()]);
		//						}

		// alerte
		if (1 == $quantity) {
			$this->addFlash('success', 'Construction d\''.(ShipResource::isAFemaleShipName($shipIdentifier) ? 'une ' : 'un ').ShipResource::getInfo($shipIdentifier, 'codeName').' commandée');
		} else {
			$this->addFlash('success', 'Construction de '.$quantity.' '.ShipResource::getInfo($shipIdentifier, 'codeName').Format::addPlural($quantity).' commandée');
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
