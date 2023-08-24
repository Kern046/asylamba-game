<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Classes\Library\Game;
use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Model\Place;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Model\TechnologyId;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Colonize extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		CommanderArmyHandler $commanderArmyHandler,
		ColorRepositoryInterface $colorRepository,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		TechnologyRepositoryInterface $technologyRepository,
		PlaceRepositoryInterface $placeRepository,
		PlayerManager $playerManager,
		EntityManagerInterface $entityManager,
		Uuid $id,
	): Response {
		// load the technologies
		$technologies = $technologyRepository->getPlayerTechnology($currentPlayer);

		// check si technologie CONQUEST débloquée
		if (1 !== $technologies->getTechnology(TechnologyId::COLONIZATION)) {
			throw new ConflictHttpException('Vous devez développer votre technologie colonisation.');
		}
		// check si la technologie BASE_QUANTITY a un niveau assez élevé
		$maxBasesQuantity = $technologies->getTechnology(TechnologyId::BASE_QUANTITY) + 1;

		$coloQuantity = 0;
		$commanders = $commanderRepository->getPlayerCommanders($currentPlayer, [Commander::MOVING]);
		foreach ($commanders as $commander) {
			if (Commander::COLO == $commander->travelType) {
				++$coloQuantity;
			}
		}
		$totalBases = $currentPlayerBasesRegistry->count() + $coloQuantity;
		if ($totalBases >= $maxBasesQuantity) {
			throw new ConflictHttpException('Vous avez assez de conquête en cours ou un niveau administration étendue trop bas.');
		}

		$commander = $commanderRepository->get($id) ?? throw $this->createNotFoundException('Commander not found');

		// TODO Voter
		if ($commander->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('Ce commandant ne vous appartient pas ou n\'existe pas.');
		}
		$placeId = $request->query->get('placeId') ?? throw new BadRequestHttpException('Missing place id');

		if (!Uuid::isValid($placeId)) {
			throw new BadRequestHttpException('Invalid place id');
		}

		$place = $placeRepository->get(Uuid::fromString($placeId)) ?? throw $this->createNotFoundException('Place not found');
		if (Place::TERRESTRIAL !== $place->typeOfPlace) {
			throw new ConflictHttpException('Ce lieu n\'est pas habitable.');
		}

		$home = $commander->base;

		$length = Game::getDistance(
			$home->place->system->xPosition,
			$place->system->xPosition,
			$home->place->system->yPosition,
			$place->system->yPosition
		);
		$duration = Game::getTimeToTravel($home->place, $place, $currentPlayerBonusRegistry->getPlayerBonus());

		// compute price
		$price = $totalBases * $this->getParameter('ares.coeff.colonization_cost');

		// calcul du bonus
		$factionBonuses = ColorResource::getInfo($currentPlayer->faction->identifier, 'bonus');
		if (in_array(ColorResource::COLOPRICEBONUS, $factionBonuses)) {
			$price -= round($price * ColorResource::BONUS_CARDAN_COLO / 100);
		}

		if (!$currentPlayer->canAfford($price)) {
			throw new ConflictHttpException('Vous n\'avez pas assez de crédits pour coloniser cette planète.');
		}
		if (0 === $commanderArmyHandler->getPev($commander)) {
			throw new ConflictHttpException('Vous devez affecter au moins un vaisseau à votre officier.');
		}
		if (!$commander->isAffected()) {
			throw new ConflictHttpException('Cet officier n\'est pas en orbite.');
		}
		$sector = $place->system->sector;

		$sectorColor = $sector->faction;
		$isFactionSector = $sector->faction?->id === $commander->player->faction->id
			|| Color::ALLY === $sectorColor?->relations[$currentPlayer->faction->identifier];

		if ($length > Commander::DISTANCEMAX && !$isFactionSector) {
			throw new ConflictHttpException('Cet emplacement est trop éloigné.');
		}
		$commanderManager->move($commander, $place, $commander->base->place, Commander::COLO, $duration);

		// debit credit
		$playerManager->decreaseCredit($currentPlayer, $price);

		$entityManager->flush();

		$this->addFlash('success', 'Flotte envoyée.');

		if ($request->query->has('redirect')) {
			return $this->redirectToRoute('map', ['place' => $request->query->get('redirect')]);
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
