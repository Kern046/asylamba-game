<?php

namespace App\Modules\Ares\Application\Handler;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class CommanderExperienceHandler
{
	public function __construct(
		private CommanderRepositoryInterface $commanderRepository,
		private PlayerManager $playerManager,
		#[Autowire('%ares.commander.base_level%')]
		private int $commanderBaseLevel,
	) {
	}

	public function setEarnedExperience(Commander $commander, Commander $enemyCommander): void
	{
		$importance = $commander->isAttacker ? LiveReport::$attackerImportance : LiveReport::$defenderImportance;

		$commander->earnedExperience = $importance * Commander::COEFFEARNEDEXP;

		if (null !== $commander->player) {
			$exp = round($commander->earnedExperience / Commander::COEFFEXPPLAYER);
			$this->playerManager->increaseExperience($commander->player, $exp);

			if ($enemyCommander->isAttacker) {
				LiveReport::$expPlayerD = $exp;
			} else {
				LiveReport::$expPlayerA = $exp;
			}
		}
	}

	public function upExperience(Commander $commander, int $earnedExperience): void
	{
		$commander->experience += $earnedExperience;

		while ($commander->experience >= $this->experienceToLevelUp($commander)) {
			++$commander->level;
		}

		$this->commanderRepository->save($commander);
	}

	public function nbLevelUp(int $level, int $newExperience): int
	{
		$oLevel = $level;
		$nLevel = $level;
		while (1) {
			if ($newExperience >= (pow(2, $nLevel) * $this->commanderBaseLevel)) {
				++$nLevel;
			} else {
				break;
			}
		}

		return $nLevel - $oLevel;
	}

	public function experienceToLevelUp(Commander $commander): int
	{
		return intval(pow(2, $commander->level) * $this->commanderBaseLevel);
	}
}
