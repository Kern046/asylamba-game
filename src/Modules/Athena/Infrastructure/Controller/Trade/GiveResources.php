<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade;

use App\Classes\Library\DateTimeConverter;
use App\Classes\Library\Format;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Domain\Service\CountNeededCommercialShips;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Message\Trade\CommercialShippingMessage;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Travel\Domain\Model\TravelType;
use App\Modules\Travel\Domain\Service\GetTravelDuration;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class GiveResources extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		GetTravelDuration $getTravelDuration,
		MessageBusInterface $messageBus,
		OrbitalBase $currentBase,
		OrbitalBaseManager $orbitalBaseManager,
		OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		OrbitalBaseHelper $orbitalBaseHelper,
		CommercialShippingRepositoryInterface $commercialShippingRepository,
		NotificationRepositoryInterface $notificationRepository,
		CountNeededCommercialShips $countNeededCommercialShips,
	): Response {
		$baseId = $request->request->get('baseId') ?? throw new BadRequestHttpException('Missing base id');
		$quantity = $request->request->get('quantity') ?? throw new BadRequestHttpException('Missing quantity');

		if (!Uuid::isValid($baseId)) {
			throw new BadRequestHttpException('Base id is invalid');
		}

		$baseUuid = Uuid::fromString($baseId);
		if ($currentBase->id->equals($baseUuid)) {
			throw new BadRequestHttpException('envoi de ressources impossible - action inutile, vos ressources sont déjà sur cette base orbitale');
		}
		$resource = intval($quantity);
		if ($resource === 0) {
			throw new BadRequestHttpException('envoi de ressources impossible - il faut envoyer un nombre entier positif');
		}
		if ($currentBase->resourcesStorage < $resource) {
			throw new ConflictHttpException('envoi de ressources impossible - vous ne pouvez pas envoyer plus que ce que vous possédez');
		}
		// ---------------------------
		// controler le nombre de vaisseaux
		// verif : have we enough commercialShips
		$totalShips = $orbitalBaseHelper->getBuildingInfo(6, 'level', $currentBase->levelCommercialPlateforme, 'nbCommercialShip');
		$usedShips = 0;

		$commercialShippings = $commercialShippingRepository->getByBase($currentBase);

		foreach ($commercialShippings as $commercialShipping) {
			if ($commercialShipping->originBase->id->equals($currentBase->id)) {
				$usedShips += $commercialShipping->shipQuantity;
			}
		}

		// TODO service to calculate needed commercial ships number
		$remainingShips = $totalShips - $usedShips;
		$commercialShipQuantity = $countNeededCommercialShips(Transaction::TYP_RESOURCE, $resource);

		if ($remainingShips < $commercialShipQuantity) {
			throw new ConflictHttpException('envoi de ressources impossible - vous n\'avez pas assez de vaisseaux de transport');
		}
		$otherBase = $orbitalBaseRepository->get($baseUuid)
			?? throw $this->createNotFoundException('envoi de ressources impossible - erreur dans les bases orbitales');

		$departure = new \DateTimeImmutable();

		// création du convoi
		$cs = new CommercialShipping(
			id: Uuid::v4(),
			player: $currentPlayer,
			originBase: $currentBase,
			destinationBase: $otherBase,
			resourceTransported: $resource,
			shipQuantity: $commercialShipQuantity,
			statement: CommercialShipping::ST_GOING,
			departureDate: $departure,
			arrivalDate: $getTravelDuration(
				origin: $currentBase->place,
				destination: $otherBase->place,
				departureDate: $departure,
				travelType: TravelType::CommercialShipping,
				player: $currentPlayer
			),
		);
		$commercialShippingRepository->save($cs);

		$messageBus->dispatch(
			new CommercialShippingMessage($cs->id),
			[DateTimeConverter::to_delay_stamp($cs->getArrivalDate())],
		);

		$orbitalBaseManager->decreaseResources($currentBase, $resource);

		if ($currentBase->player->id !== $otherBase->player->id) {
			$notification = NotificationBuilder::new()
				->setTitle('Envoi de ressources')
				->setContent(
					NotificationBuilder::paragraph(
						NotificationBuilder::link(
							$this->generateUrl('embassy', ['player' => $currentPlayer->id]),
							$currentPlayer->name,
						),
						' a lancé un convoi de ',
						NotificationBuilder::bold(Format::numberFormat($resource)),
						' ressources depuis sa base ',
						NotificationBuilder::link(
							$this->generateUrl('map', ['place' => $currentBase->place->id]),
							$currentBase->name,
						),
						'.',
					),
					NotificationBuilder::paragraph(
						'Quand le convoi arrivera, les ressources seront à vous.',
						NotificationBuilder::divider(),
						NotificationBuilder::link(
							$this->generateUrl('switchbase', ['baseId' => $otherBase->id, 'page' => 'market']),
							'vers la place du commerce →',
						),
					),
				)
				->for($otherBase->player);
			$notificationRepository->save($notification);
		}

		$this->addFlash('success', 'Ressources envoyées');

		return $this->redirect($request->headers->get('referer'));
	}
}
