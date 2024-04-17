<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Application\Handler\Movement\MoveFleet;
use App\Modules\Ares\Domain\Event\Fleet\PlannedLootEvent;
use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Application\Handler\GetDistanceBetweenPlaces;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Model\Place;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class Loot extends AbstractController
{
	public function __construct(
		private readonly CommanderRepositoryInterface $commanderRepository,
		private readonly EntityManagerInterface $entityManager,
		private readonly EventDispatcherInterface $eventDispatcher,
	) {
	}

	#[Route(
		path: '/commanders/{id}/loot',
		name: 'loot',
		methods: Request::METHOD_GET,
	)]
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		GetDistanceBetweenPlaces $getDistanceBetweenPlaces,
		MoveFleet $moveFleet,
		PlaceRepositoryInterface $placeRepository,
		CommanderArmyHandler $commanderArmyHandler,
		Uuid $id,
	): Response {
		$placeId = $request->query->get('placeId') ?? throw new BadRequestHttpException('Missing place ID');

		if (!Uuid::isValid($placeId)) {
			throw new BadRequestHttpException('The given place ID is not a valid UUID');
		}

		// @TODO simplify this hell
		$place = $placeRepository->get(Uuid::fromString($placeId))
			?? throw $this->createNotFoundException('Place not found');
		$commander = $this->commanderRepository->get($id)
			?? throw $this->createNotFoundException('Commander not found');

		if ($commander->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('This commander does not belong to you');
		}

		$home = $commander->base;

		// TODO replace with proper services
		$length = $getDistanceBetweenPlaces($home->place, $place);

		if (0 === $commanderArmyHandler->getPev($commander)) {
			throw new ConflictHttpException('You cannot send a commander with an empty fleet');
		}

		$sector = $place->system->sector;
		$sectorColor = $sector->faction;
		// Move that part in a Specification class
		$isFactionSector = $sectorColor->id === $currentPlayer->faction->id || Color::ALLY == $sectorColor->relations[$currentPlayer->faction->identifier];

		// Move that part in a Specification class
		if ($length > Commander::DISTANCEMAX && !$isFactionSector) {
			throw new ConflictHttpException('Ce lieu est trop éloigné.');
		}

		// Move that part in a Specification class
		if (null !== ($targetPlayer = $place->player) && 1 === $targetPlayer->level && !in_array($place->player->statement, [Player::DELETED, Player::DEAD])) {
			throw new ConflictHttpException('Vous ne pouvez pas piller un joueur actif de niveau 1.');
		}

		$faction = $currentPlayer->faction;

		// Move that part in a Specification class
		if (null !== $targetPlayer && ($faction->id === $targetPlayer->faction->id || Color::ALLY === $faction->relations[$targetPlayer->faction->identifier])) {
			throw new ConflictHttpException('You cannot loot an ally planet');
		}

		if (Place::TERRESTRIAL !== $place->typeOfPlace) {
			throw new ConflictHttpException('This place is not inhabited');
		}
		$moveFleet(
			commander: $commander,
			origin: $home->place,
			destination: $place,
			mission: CommanderMission::Loot,
		);

		$this->addFlash('success', 'Flotte envoyée.');

		$this->entityManager->flush();

		$this->eventDispatcher->dispatch(new PlannedLootEvent($place, $commander, $currentPlayer));

		if ($request->query->has('redirect')) {
			return $this->redirectToRoute('map', ['place' => $request->query->get('redirect')]);
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
