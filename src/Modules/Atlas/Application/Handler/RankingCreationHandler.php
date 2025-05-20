<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Application\Handler;

use App\Modules\Atlas\Application\Message\RankingCreationMessage;
use App\Modules\Atlas\Domain\Repository\RankingRepositoryInterface;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Atlas\Routine\FactionRoutineHandler;
use App\Modules\Atlas\Routine\PlayerRoutineHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class RankingCreationHandler
{
	public function __construct(
		private RankingRepositoryInterface $rankingRepository,
		private PlayerRoutineHandler $playerRoutineHandler,
		private FactionRoutineHandler $factionRoutineHandler,
	) {
	}

	public function __invoke(RankingCreationMessage $message): void
	{
		$ranking = new Ranking(
			id: Uuid::v4(),
			createdAt: new \DateTimeImmutable(),
		);

		$this->rankingRepository->save($ranking);

		$this->playerRoutineHandler->process($ranking);
		$this->factionRoutineHandler->process($ranking);
	}
}
