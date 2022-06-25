<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Classes\Library\Game;
use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Infrastructure\Validator\Commander\CanConquer;
use App\Modules\Ares\Infrastructure\Validator\DTO\Conquest;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Handler\CountPlayerBases;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Conquer extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $orbitalBase,
		CountPlayerBases $countPlayerBases,
		CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		PlaceRepositoryInterface $placeRepository,
		PlayerManager $playerManager,
		TechnologyRepositoryInterface $technologyRepository,
		EntityManagerInterface $entityManager,
		CommanderArmyHandler $commanderArmyHandler,
		ValidatorInterface $validator,
		Uuid $id,
	): Response {
		$conquestCost = $this->getParameter('ares.coeff.conquest_cost');
		$placeId = $request->query->get('placeId') ?? throw new BadRequestHttpException('Missing place id');

		if (!Uuid::isValid($placeId)) {
			throw new BadRequestHttpException('Invalid place id');
		}

		$place = $placeRepository->get(Uuid::fromString($placeId)) ?? throw $this->createNotFoundException('Place not found');

		$commander = $commanderRepository->get($id) ?? throw $this->createNotFoundException('Commander not found');
		// TODO Voter
		if ($commander->player->id !== $currentPlayer->id) {
			throw $this->createAccessDeniedException('Ce commandant ne vous appartient pas');
		}
		$home = $commander->base;

		$totalBases = $countPlayerBases($currentPlayer);
		// compute price
		$price = $totalBases * $conquestCost;

		// TODO factorize faction bonus in BonusApplier
		$factionBonus = ColorResource::getInfo($currentPlayer->faction->identifier, 'bonus');
		// calcul du bonus
		if (in_array(ColorResource::COLOPRICEBONUS, $factionBonus)) {
			$price -= round($price * ColorResource::BONUS_CARDAN_COLO / 100);
		}

		$technologies = $technologyRepository->getPlayerTechnology($currentPlayer);
		$validator->validate(new Conquest(
			commander: $commander,
			attackerTechnology: $technologies,
			attackerBasesCount: $totalBases,
			targetedPlace: $place,
		), new CanConquer($price));

		$duration = Game::getTimeToTravel($home->place, $place, $currentPlayerBonusRegistry->getPlayerBonus());
		$commanderManager->move($commander, $place, $commander->base->place, Commander::COLO, $duration);
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
