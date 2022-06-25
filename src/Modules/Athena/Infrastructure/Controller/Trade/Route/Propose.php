<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Propose extends AbstractController
{
	public function __invoke(
		Request $request,
		OrbitalBase $currentBase,
		Player $currentPlayer,
		OrbitalBaseHelper $orbitalBaseHelper,
		OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		CommercialRouteRepositoryInterface $commercialRouteRepository,
		NotificationRepositoryInterface $notificationRepository,
		PlayerManager $playerManager,
	): Response {
		$baseTo = $request->query->get('destinationBase') ?? throw new BadRequestHttpException('Missing destination base');

		if (!Uuid::isValid($baseTo)) {
			throw new BadRequestHttpException('Invalid destination base ID');
		}

		$otherBase = $orbitalBaseRepository->get(Uuid::fromString($baseTo)) ?? throw $this->createNotFoundException('Destination base not found');

		$nbrMaxCommercialRoute = $orbitalBaseHelper->getBuildingInfo(OrbitalBaseResource::SPATIOPORT, 'level', $currentBase->levelSpatioport, 'nbRoutesMax');

		// Check if a route already exists between these two bases
		$alreadyARoute = null !== $commercialRouteRepository->getExistingRoute($currentBase, $otherBase);

		// TODO transform into validation constraint
		if (($commercialRouteRepository->countBaseRoutes($currentBase) >= $nbrMaxCommercialRoute) || $alreadyARoute || $currentBase->levelSpatioport === 0 || $otherBase->levelSpatioport === 0) {
			throw new ConflictHttpException('Impossible de proposer une route commerciale');
		}
		$player = $otherBase->player;

		$playerFaction = $currentPlayer->faction;
		$otherFaction = $player->faction;

		// TODO move to validation constraint
		if (Color::ENEMY === $playerFaction->relations[$otherFaction->identifier] || Color::ENEMY === $otherFaction->relations[$playerFaction->identifier]) {
			throw new ConflictHttpException('impossible de proposer une route commerciale à ce joueur, vos factions sont en guerre.');
		}
		// TODO move to validation constraint
		if ($currentBase->player->id === $otherBase->player->id) {
			throw new ConflictHttpException('Vous ne pouvez pas créer de route commerciale avec votre propre planète');
		}
		$distance = Game::getDistance(
			$currentBase->place->system->xPosition,
			$otherBase->place->system->xPosition,
			$currentBase->place->system->yPosition,
			$otherBase->place->system->yPosition,
		);
		$bonusA = ($currentBase->place->system->sector->id !== $otherBase->place->system->sector->id) ? $this->getParameter('athena.trade.route.sector_bonus') : 1;
		$bonusB = ($playerFaction->identifier !== $otherFaction->identifier) ? $this->getParameter('athena.trade.route.color_bonus') : 1;

		$price = Game::getRCPrice($distance);
		$income = Game::getRCIncome($distance, $bonusA, $bonusB);

		if (1 == $distance) {
			$imageLink = '1-'.rand(1, 3);
		} elseif ($distance < 26) {
			$imageLink = '2-'.rand(1, 3);
		} elseif ($distance < 126) {
			$imageLink = '3-'.rand(1, 3);
		} else {
			$imageLink = '4-'.rand(1, 3);
		}

		// TODO Refactor faction economic bonuses to merge with palyer bonus management
		$factionBonus = ColorResource::getInfo($playerFaction->identifier, 'bonus');
		// compute bonus
		if (in_array(ColorResource::COMMERCIALROUTEPRICEBONUS, $factionBonus)) {
			$priceWithBonus = round($price - ($price * ColorResource::BONUS_NEGORA_ROUTE / 100));
		} else {
			$priceWithBonus = $price;
		}

		if (!$currentPlayer->canAfford($priceWithBonus)) {
			throw new ConflictHttpException('impossible de proposer une route commerciale - vous n\'avez pas assez de crédits');
		}
		// création de la route
		$cr = new CommercialRoute(
			id: Uuid::v4(),
			originBase: $currentBase,
			destinationBase: $otherBase,
			imageLink: $imageLink,
			distance: $distance,
			price: $price,
			income: $income,
			proposedAt: new \DateTimeImmutable(),
			acceptedAt: null,
			statement: CommercialRoute::PROPOSED,
		);
		$commercialRouteRepository->save($cr);

		// débit des crédits au joueur
		$playerManager->decreaseCredit($currentPlayer, $priceWithBonus);

		$notification = NotificationBuilder::new()
			->setTitle('Proposition de route commerciale')
			->setContent(NotificationBuilder::paragraph(
				NotificationBuilder::link(
					$this->generateUrl('embassy', ['player' => $currentPlayer->id]),
					$currentPlayer->name,
				),
				' vous propose une route commerciale liant ',
				NotificationBuilder::link(
					$this->generateUrl('map', ['place' => $currentBase->place->id]),
					$currentBase->name,
				),
				' et ',
				NotificationBuilder::link(
					$this->generateUrl('map', ['place' => $otherBase->place->id]),
					$otherBase->name,
				),
				'.',
				NotificationBuilder::divider(),
				'Les frais de l\'opération vous coûteraient ',
				Format::numberFormat($priceWithBonus),
				' crédits; Les gains estimés pour cette route sont de ',
				Format::numberFormat($income),
				' crédits par relève.',
				NotificationBuilder::divider(),
				NotificationBuilder::link(
					$this->generateUrl('switchbase', ['baseId' => $otherBase->id, 'page' => 'spatioport']),
					'En savoir plus ?',
				),
			))
			->for($player);
		$notificationRepository->save($notification);

		$this->addFlash('success', 'Route commerciale proposée');

		return $this->redirect($request->headers->get('referer'));
	}
}
