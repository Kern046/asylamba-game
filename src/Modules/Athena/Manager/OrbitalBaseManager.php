<?php

namespace App\Modules\Athena\Manager;

use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Domain\Event\BaseOwnerChangeEvent;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\GetMaxStorage;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

readonly class OrbitalBaseManager
{
	public function __construct(
		private GetMaxStorage $getMaxStorage,
		private CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		private TechnologyQueueRepositoryInterface $technologyQueueRepository,
		private CommercialRouteManager $commercialRouteManager,
		private CommercialShippingRepositoryInterface $commercialShippingRepository,
		private CommanderRepositoryInterface $commanderRepository,
		private TransactionRepositoryInterface $transactionRepository,
		private RecyclingMissionRepositoryInterface $recyclingMissionRepository,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private EntityManagerInterface $entityManager,
		private EventDispatcherInterface $eventDispatcher,
		private CommanderManager $commanderManager,
	) {
	}

	/**
	 * @param list<Commander> $movingCommanders
	 */
	public function getPlayerBasesCount(array $movingCommanders): int
	{
		$coloQuantity = \count(\array_filter(
			$movingCommanders,
			fn (Commander $commander) => CommanderMission::Colo === $commander->travelType,
		));

		return $coloQuantity + $this->currentPlayerBasesRegistry->count();
	}

	public function changeOwner(OrbitalBase $base, Player|null $newOwner): void
	{
		$baseCommanders = $this->commanderRepository->getBaseCommanders($base);
		// changement de possesseur des offres du marché
		$transactions = $this->transactionRepository->getBasePropositions($base);

		foreach ($transactions as $transaction) {
			// change owner of transaction
			$transaction->rPlayer = $newOwner;

			$commercialShipping = $this->commercialShippingRepository->getByTransaction($transaction);
			// change owner of commercial shipping
			$commercialShipping->player = $newOwner;
		}

		// attribuer le rPlayer à la Base
		$oldOwner = $base->player;
		$base->player = $newOwner;

		// suppression des routes commerciales
		$this->commercialRouteManager->removeBaseRoutes($base);

		// suppression des technologies en cours de développement
		foreach ($this->technologyQueueRepository->getPlaceQueues($base->place) as $queue) {
			$this->technologyQueueRepository->remove($queue);
		}

		// suppression des missions de recyclages ainsi que des logs de recyclages
		$this->recyclingMissionRepository->removeBaseMissions($base);

		// mise des investissements à 0
		$base->iSchool = 0;
		$base->iAntiSpy = 0;

		// mise à jour de la date de création pour qu'elle soit dans l'ordre
		$base->createdAt = new \DateTimeImmutable();
		// rendre déserteuses les flottes en voyage
		foreach ($baseCommanders as $commander) {
			if (in_array($commander->statement, [Commander::INSCHOOL, Commander::ONSALE, Commander::RESERVE])) {
				$commander->player = $newOwner;
			} elseif ($commander->isMoving()) {
				// TODO replace "prise en vol"
				$this->commanderManager->endTravel($commander, Commander::RETIRED);
			// @TODO handle cancellation
			// $this->realtimeActionScheduler->cancel($commander, $commander->getArrivalDate());
			} else {
				$commander->statement = Commander::DEAD;
			}
		}

		$this->eventDispatcher->dispatch(new BaseOwnerChangeEvent($base, $oldOwner));

		$this->entityManager->flush();
	}

	public function increaseResources(
		OrbitalBase $orbitalBase,
		int $resources,
		bool $persist = true
	): void {
		$orbitalBase->resourcesStorage = min(
			$orbitalBase->resourcesStorage + $resources,
			($this->getMaxStorage)(base: $orbitalBase, offLimits: true),
		);

		if (true === $persist) {
			$this->orbitalBaseRepository->save($orbitalBase);
		}
	}

	public function decreaseResources(OrbitalBase $orbitalBase, int $resources): void
	{
		$orbitalBase->resourcesStorage = max($orbitalBase->resourcesStorage - $resources, 0);

		$this->orbitalBaseRepository->save($orbitalBase);
	}
}
