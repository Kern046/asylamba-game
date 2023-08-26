<?php

namespace App\Modules\Athena\Handler;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Athena\Domain\Repository\RecyclingLogRepositoryInterface;
use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Message\RecyclingMissionMessage;
use App\Modules\Athena\Model\RecyclingLog;
use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Model\Place;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Shared\Application\Handler\DurationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class RecyclingMissionHandler
{
	public function __construct(
		private DurationHandler $durationHandler,
		private EntityManagerInterface $entityManager,
		private OrbitalBaseManager                  $orbitalBaseManager,
		private PlaceManager                        $placeManager,
		private PlayerManager                       $playerManager,
		private NotificationRepositoryInterface                 $notificationRepository,
		private RecyclingMissionRepositoryInterface $recyclingMissionRepository,
		private RecyclingLogRepositoryInterface     $recyclingLogRepository,
		private MessageBusInterface                 $messageBus,
		private UrlGeneratorInterface $urlGenerator,
	) {
	}

	public function __invoke(RecyclingMissionMessage $message): void
	{
		$mission = $this->recyclingMissionRepository->get($message->getRecyclingMissionId());
		$orbitalBase = $mission->base;
		$targetPlace = $mission->target;

		$player = $orbitalBase->player;
		if (Place::EMPTYZONE !== $targetPlace->typeOfPlace) {
			// make the recycling : decrease resources on the target place
			$totalRecycled = $mission->recyclerQuantity * RecyclingMission::RECYCLER_CAPACTIY;
			$targetPlace->resources -= $totalRecycled;
			// if there is no more resource
			if ($targetPlace->resources <= 0) {
				// Avoid out of range errors
				$targetPlace->resources = 0;
				// the place become an empty place
				$this->placeManager->turnAsEmptyPlace($targetPlace);

				// stop the mission
				$mission->statement = RecyclingMission::ST_DELETED;

				// send notification to the player
				$notification = NotificationBuilder::new()
					->setTitle('Arrêt de mission de recyclage')
					->setContent(NotificationBuilder::paragraph(
						'Un ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $mission->target->id]),
							'lieu',
						),
						' que vous recycliez est désormais totalement dépourvu de ressources et s\'est donc transformé en lieu vide.',
						NotificationBuilder::divider(),
						'Vos recycleurs restent donc stationnés sur votre ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $orbitalBase->place->id]),
							'base orbitale',
						),
						' le temps que vous programmiez une autre mission.',
					))
					->for($player);

				$this->notificationRepository->save($notification);
			}

			// if the sector change its color between 2 recyclings
			if ($player->faction->id !== $targetPlace->system->sector->faction?->id) {
				// stop the mission
				$mission->statement = RecyclingMission::ST_DELETED;

				$this->notificationRepository->save(NotificationBuilder::new()
					->setTitle('Arrêt de mission de recyclage')
					->setContent(NotificationBuilder::paragraph(
						'Le secteur d\'un ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $mission->target->id]),
							'lieu',
						),
						' que vous recycliez est passé à l\'ennemi, vous ne pouvez donc plus y envoyer vos recycleurs. La mission est annulée.',
						NotificationBuilder::divider(),
						'Vos recycleurs restent donc stationnés sur votre ',
						NotificationBuilder::link(
							$this->urlGenerator->generate('map', ['place' => $mission->base->place->id]),
							'base orbitale',
						),
						' le temps que vous programmiez une autre mission.',
					))
					->for($player));
			}

			$creditRecycled = round($targetPlace->population * $totalRecycled * 10 / 100);
			$resourceRecycled = round($targetPlace->coefResources * $totalRecycled / 100);
			$shipRecycled = round($targetPlace->coefHistory * $totalRecycled / 100);

			// diversify a little (resource and credit)
			$percent = rand(-5, 5);
			$diffAmountCredit = round($creditRecycled * $percent / 100);
			$diffAmountResource = round($resourceRecycled * $percent / 100);
			$creditRecycled += $diffAmountCredit;
			$resourceRecycled -= $diffAmountResource;

			if ($creditRecycled < 0) {
				$creditRecycled = 0;
			}
			if ($resourceRecycled < 0) {
				$resourceRecycled = 0;
			}

			// convert shipRecycled to real ships
			$pointsToRecycle = round($shipRecycled * RecyclingMission::COEF_SHIP);
			$shipsArray1 = [];
			$buyShip = [];
			for ($i = 0; $i < ShipResource::SHIP_QUANTITY; ++$i) {
				if (floor($pointsToRecycle / ShipResource::getInfo($i, 'resourcePrice')) > 0) {
					$shipsArray1[] = [
						'ship' => $i,
						'price' => ShipResource::getInfo($i, 'resourcePrice'),
						'canBuild' => true, ];
				}
				$buyShip[] = 0;
			}

			shuffle($shipsArray1);
			$shipsArray = [];
			$onlyThree = 0;
			foreach ($shipsArray1 as $key => $value) {
				++$onlyThree;
				$shipsArray[] = $value;
				if (3 == $onlyThree) {
					break;
				}
			}
			$continue = true;
			if (count($shipsArray) > 0) {
				while ($continue) {
					foreach ($shipsArray as $key => $line) {
						if ($line['canBuild']) {
							$nbmax = floor($pointsToRecycle / $line['price']);
							if ($nbmax < 1) {
								$shipsArray[$key]['canBuild'] = false;
							} else {
								$qty = rand(1, $nbmax);
								$pointsToRecycle -= $qty * $line['price'];
								$buyShip[$line['ship']] += $qty;
							}
						}
					}

					$canBuild = false;
					// verify if we can build one more ship
					foreach ($shipsArray as $key => $line) {
						if ($line['canBuild']) {
							$canBuild = true;
							break;
						}
					}
					if (!$canBuild) {
						// if the 3 types of ships can't be build anymore --> stop
						$continue = false;
					}
				}
			}

			// create a RecyclingLog
			$rl = new RecyclingLog(
				id: Uuid::v4(),
				mission: $mission,
				createdAt: new \DateTimeImmutable(),
				resources: $resourceRecycled,
				credits: $creditRecycled,
				ship0: $buyShip[0],
				ship1: $buyShip[1],
				ship2: $buyShip[2],
				ship3: $buyShip[3],
				ship4: $buyShip[4],
				ship5: $buyShip[5],
				ship6: $buyShip[6],
				ship7: $buyShip[7],
				ship8: $buyShip[8],
				ship9: $buyShip[9],
				ship10: $buyShip[10],
				ship11: $buyShip[11],
			);

			$this->recyclingLogRepository->save($rl);

			// give to the orbitalBase ($orbitalBase) and player what was recycled
			$this->orbitalBaseManager->increaseResources($orbitalBase, $resourceRecycled);
			for ($i = 0; $i < ShipResource::SHIP_QUANTITY; ++$i) {
				$orbitalBase->addShips($i, $buyShip[$i]);
			}
			$this->playerManager->increaseCredit($player, $creditRecycled);

			// add recyclers waiting to the mission
			$mission->recyclerQuantity += $mission->addToNextMission;
			$mission->addToNextMission = 0;

			// if a mission is stopped by the user, delete it
			if (RecyclingMission::ST_BEING_DELETED == $mission->statement) {
				$mission->statement = RecyclingMission::ST_DELETED;
			}

			// update u
			$mission->endedAt = $this->durationHandler->getDurationEnd($mission->endedAt, $mission->cycleTime);
			// Schedule the next mission if there is still resources
			if (RecyclingMission::ST_DELETED !== $mission->statement) {
				$this->messageBus->dispatch(
					new RecyclingMissionMessage($mission->id),
					[DateTimeConverter::to_delay_stamp($mission->endedAt)]
				);
			}
		} else {
			// the place become an empty place
			$targetPlace->resources = 0;

			// stop the mission
			$mission->statement = RecyclingMission::ST_DELETED;

			$this->notificationRepository->save(NotificationBuilder::new()
				->setTitle('Arrêt de mission de recyclage')
				->setContent(NotificationBuilder::paragraph(
					'Un ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('map', ['place' => $mission->target->id]),
						'lieu',
					),
					' que vous recycliez est désormais totalement dépourvu de ressources et s\'est donc transformé en lieu vide.',
					NotificationBuilder::divider(),
					'Vos recycleurs restent donc stationnés sur votre ',
					NotificationBuilder::link(
						$this->urlGenerator->generate('map', ['place' => $mission->base->place->id]),
						'base orbitale',
					),
					' le temps que vous programmiez une autre mission.',
				))
				->for($player));
		}
		$this->entityManager->flush();
	}
}
