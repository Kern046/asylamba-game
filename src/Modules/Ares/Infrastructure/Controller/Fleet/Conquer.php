<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Classes\Library\Game;
use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
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

class Conquer extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $orbitalBase,
		CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		PlaceRepositoryInterface $placeRepository,
		PlayerManager $playerManager,
		TechnologyRepositoryInterface $technologyRepository,
		EntityManagerInterface $entityManager,
		CommanderArmyHandler $commanderArmyHandler,
		Uuid $id,
	): Response {
		$conquestCost = $this->getParameter('ares.coeff.conquest_cost');
		$placeId = $request->query->get('placeId') ?? throw new BadRequestHttpException('Missing place id');

		if (!Uuid::isValid($placeId)) {
			throw new BadRequestHttpException('Invalid place id');
		}

		$place = $placeRepository->get(Uuid::fromString($placeId)) ?? throw $this->createNotFoundException('Place not found');

		// load the technologies
		$technologies = $technologyRepository->getPlayerTechnology($currentPlayer);

		// check si technologie CONQUEST débloquée
		if (1 !== $technologies->getTechnology(TechnologyId::CONQUEST)) {
			throw new ConflictHttpException('Vous devez débloquer la technologie de conquête.');
		}
		// check si la technologie BASE_QUANTITY a un niveau assez élevé
		$maxBasesQuantity = $technologies->getTechnology(TechnologyId::BASE_QUANTITY) + 1;
		// @TODO Replace this count loop by a repository method (and a dedicated service for bases count)
		$coloQuantity = 0;
		$commanders = $commanderRepository->getPlayerCommanders($currentPlayer, [Commander::MOVING]);
		foreach ($commanders as $commander) {
			if (Commander::COLO === $commander->travelType) {
				++$coloQuantity;
			}
		}
		$totalBases = $currentPlayerBasesRegistry->count() + $coloQuantity;
		if ($totalBases >= $maxBasesQuantity) {
			throw new ConflictHttpException('Vous avez assez de conquête en cours ou un niveau d\'administration étendue trop faible.');
		}

		$targetPlayer = $place->player ?? throw new ConflictHttpException('This planet does not belong to a player');

		if ($targetPlayer->level <= 3 && !in_array($targetPlayer->statement, [Player::DELETED, Player::DEAD])) {
			throw new ConflictHttpException('Vous ne pouvez pas conquérir un joueur de niveau 3 ou moins.');
		}
		$commander = $commanderRepository->get($id) ?? throw $this->createNotFoundException('Commander not found');
		// TODO Voter
		if ($commander->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('Ce commandant ne vous appartient pas');
		}
		$color = $currentPlayer->faction;

		if ($color->id === $place->player->faction->id || Color::ALLY === $color->relations[$targetPlayer->faction->identifier]) {
			throw new ConflictHttpException('Vous ne pouvez pas attaquer un lieu appartenant à votre Faction ou d\'une faction alliée.');
		}
		$home = $commander->base;

		$length = Game::getDistance(
			$home->place->system->xPosition,
			$place->system->xPosition,
			$home->place->system->xPosition,
			$place->system->xPosition
		);
		$duration = Game::getTimeToTravel($home->place, $place, $currentPlayerBonusRegistry->getPlayerBonus());

		// compute price
		$price = $totalBases * $conquestCost;

		$factionBonus = ColorResource::getInfo($color->identifier, 'bonus');
		// calcul du bonus
		if (in_array(ColorResource::COLOPRICEBONUS, $factionBonus)) {
			$price -= round($price * ColorResource::BONUS_CARDAN_COLO / 100);
		}

		// TODO Specification pattern
		if (!$currentPlayer->canAfford($price)) {
			throw new ConflictHttpException('Vous n\'avez pas assez de crédits pour conquérir cette base.');
		}
		if (0 === $commanderArmyHandler->getPev($commander)) {
			throw new ConflictHttpException('Vous devez affecter au moins un vaisseau à votre officier.');
		}
		if (!$commander->isAffected()) {
			throw new ConflictHttpException('Cet officier est déjà en déplacement.');
		}
		$sector = $place->system->sector;

		$sectorFaction = $sector->faction;
		// TODO make an isAllyFaction service
		$isFactionSector = $sectorFaction?->id === $currentPlayer->faction->id || Color::ALLY === $sectorFaction?->relations[$currentPlayer->faction->identifier];

		// TODO Move that length check into a dedicated service
		if ($length <= Commander::DISTANCEMAX || $isFactionSector) {
			throw new ConflictHttpException('Cet emplacement est trop éloigné.');
		}
		$commanderManager->move($commander, $place, $commander->base->place, Commander::COLO, $length, $duration);
		// debit credit
		$playerManager->decreaseCredit($currentPlayer, $price);

		$this->addFlash('success', 'Flotte envoyée.');

		$entityManager->flush();

		if ($request->query->has('redirect')) {
			return $this->redirectToRoute('map', ['place' => $request->query->get('redirect')]);
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
