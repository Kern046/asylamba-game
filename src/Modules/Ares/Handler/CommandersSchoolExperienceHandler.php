<?php

namespace App\Modules\Ares\Handler;

use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Specification\CanEarnSchoolExperience;
use App\Modules\Ares\Message\CommandersSchoolExperienceMessage;
use App\Modules\Ares\Model\Commander;
use App\Modules\Shared\Application\Service\CountMissingSystemUpdates;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonusId;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CommandersSchoolExperienceHandler
{
	public function __construct(
		private ClockInterface $clock,
		private EntityManagerInterface $entityManager,
		private CommanderRepositoryInterface $commanderRepository,
		private CountMissingSystemUpdates $countMissingSystemUpdates,
		private PlayerBonusManager $playerBonusManager,
		private CommanderExperienceHandler $commanderExperienceHandler,
	) {
	}

	public function __invoke(CommandersSchoolExperienceMessage $message): void
	{
		$commanders = $this->commanderRepository->getBySpecification(new CanEarnSchoolExperience());
		$this->entityManager->beginTransaction();

		foreach ($commanders as $commander) {
			// If the commander was updated recently, we skip him
			$missingUpdatesCount = ($this->countMissingSystemUpdates)($commander);
			if (0 === $missingUpdatesCount) {
				continue;
			}

			$commander->updatedAt = $this->clock->now();
			$orbitalBase = $commander->base;

			$playerBonus = $this->playerBonusManager->getBonusByPlayer($commander->player);
			$playerBonus = $playerBonus->bonuses;

			for ($i = 0; $i < $missingUpdatesCount; ++$i) {
				$invest = $orbitalBase->iSchool;
				$invest += $invest * $playerBonus->get(PlayerBonusId::COMMANDER_INVEST) / 100;

				// xp gagnée
				// TODO Factorize in a service and check potential duplicates
				$earnedExperience = $invest / Commander::COEFFSCHOOL;
				$earnedExperience += (1 == rand(0, 1))
					? rand(0, intval(round($earnedExperience / 20)))
					: -(rand(0, intval(round($earnedExperience / 20))));
				$earnedExperience = max(round($earnedExperience), 0);

				$this->commanderExperienceHandler->upExperience($commander, $earnedExperience);
			}
		}
		$this->entityManager->commit();
	}
}
