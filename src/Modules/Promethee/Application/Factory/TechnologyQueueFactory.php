<?php

declare(strict_types=1);

namespace App\Modules\Promethee\Application\Factory;

use App\Classes\Library\DateTimeConverter;
use App\Classes\Library\Game;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Domain\Event\NewTechnologyQueueEvent;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Message\TechnologyQueueMessage;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Shared\Application\Handler\DurationHandler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class TechnologyQueueFactory
{
	public function __construct(
		private CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		private DurationHandler $durationHandler,
		private EventDispatcherInterface $eventDispatcher,
		private MessageBusInterface $messageBus,
		private TechnologyHelper $technologyHelper,
		private TechnologyQueueRepositoryInterface $technologyQueueRepository,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function create(
		OrbitalBase        $orbitalBase,
		int                $identifier,
		int                $targetLevel,
		\DateTimeImmutable $createdAt,
	): TechnologyQueue {
		$technologyQueue = new TechnologyQueue(
			id: Uuid::v4(),
			player: $orbitalBase->player,
			place: $orbitalBase->place,
			technology: $identifier,
			targetLevel: $targetLevel,
			startedAt: $createdAt,
			endedAt: $this->durationHandler->getDurationEnd($createdAt, $this->getDuration(
				orbitalBase: $orbitalBase,
				identifier: $identifier,
				targetLevel: $targetLevel,
			)),
		);

		$this->technologyQueueRepository->save($technologyQueue);

		$this->messageBus->dispatch(
			new TechnologyQueueMessage($technologyQueue->id),
			[DateTimeConverter::to_delay_stamp($technologyQueue->getEndDate())]
		);

		$this->eventDispatcher->dispatch(new NewTechnologyQueueEvent($technologyQueue));

		return $technologyQueue;
	}

	private function getDuration(OrbitalBase $orbitalBase, int $identifier, int $targetLevel): int
	{
		$time = $this->timeMode->isStandard()
			? $this->technologyHelper->getInfo($identifier, 'time', $targetLevel)
			: 10 * $targetLevel;

		$bonusPercent = $this->currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::TECHNOSPHERE_SPEED);
		if (ColorResource::APHERA === $orbitalBase->player->faction->identifier) {
			$bonusPercent += ColorResource::BONUS_APHERA_TECHNO;
		}

		// ajout du bonus du lieu
		$bonusPercent += Game::getImprovementFromScientificCoef($orbitalBase->place->coefHistory);
		$bonus = round($time * $bonusPercent / 100);

		return intval(round($time - $bonus));
	}
}
