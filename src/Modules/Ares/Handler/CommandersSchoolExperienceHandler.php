<?php

namespace App\Modules\Ares\Handler;

use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Specification\CanEarnSchoolExperience;
use App\Modules\Ares\Message\CommandersSchoolExperienceMessage;
use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Shared\Application\Handler\DurationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CommandersSchoolExperienceHandler
{
	public function __construct(
		private DurationHandler $durationHandler,
		private EntityManagerInterface $entityManager,
		private CommanderRepositoryInterface $commanderRepository,
		private PlayerBonusManager $playerBonusManager,
		private CommanderExperienceHandler $commanderExperienceHandler,
	) {
	}

	public function __invoke(CommandersSchoolExperienceMessage $message): void
	{
		$now = new \DateTimeImmutable();
		$commanders = $this->commanderRepository->getBySpecification(new CanEarnSchoolExperience());
		$this->entityManager->beginTransaction();

		foreach ($commanders as $commander) {
			// If the commander was updated recently, we skip him
			if (0 === ($hoursDiff = $this->durationHandler->getHoursDiff($commander->updatedAt, $now))) {
				continue;
			}

			$commander->updatedAt = new \DateTimeImmutable();
			$orbitalBase = $commander->base;

			$playerBonus = $this->playerBonusManager->getBonusByPlayer($commander->player);
			$playerBonus = $playerBonus->bonuses;

			for ($i = 0; $i < $hoursDiff; ++$i) {
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
