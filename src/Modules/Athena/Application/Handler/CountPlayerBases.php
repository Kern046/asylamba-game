<?php

namespace App\Modules\Athena\Application\Handler;

use App\Modules\Ares\Domain\Model\CommanderMission;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Zeus\Model\Player;

readonly class CountPlayerBases
{
	public function __construct(
		private CommanderRepositoryInterface $commanderRepository,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
	) {

	}

	public function __invoke(Player $player): int
	{
		$playerBases = $this->orbitalBaseRepository->getPlayerBasesCount($player);

		$conquerringCommanders = count(array_filter(
			$this->commanderRepository->getPlayerCommanders($player, [Commander::MOVING]),
			fn(Commander $commander) => CommanderMission::Colo === $commander->travelType,
		));

		return $playerBases + $conquerringCommanders;
	}
}
