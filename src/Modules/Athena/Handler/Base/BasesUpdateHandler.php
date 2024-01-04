<?php

namespace App\Modules\Athena\Handler\Base;

use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Message\Base\BasesUpdateMessage;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Zeus\Infrastructure\Validator\IsPlayerAlive;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Shared\Application\Handler\DurationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class BasesUpdateHandler
{
	public function __construct(
		private EntityManagerInterface $entityManager,
		private PlayerBonusManager $playerBonusManager,
		private OrbitalBaseManager $orbitalBaseManager,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private OrbitalBaseHelper $orbitalBaseHelper,
		private DurationHandler $durationHandler,
	) {
	}

	public function __invoke(BasesUpdateMessage $message): void
	{
		$bases = $this->orbitalBaseRepository->getBySpecification(new IsPlayerAlive());
		$this->entityManager->beginTransaction();
		$now = new \DateTimeImmutable();

		foreach ($bases as $base) {
			$hoursDiff = $this->durationHandler->getHoursDiff($base->updatedAt, $now);
			if (0 === $hoursDiff) {
				continue;
			}

			$playerBonus = $this->playerBonusManager->getBonusByPlayer($base->player);
			$base->updatedAt = $now;

			for ($i = 0; $i < $hoursDiff; ++$i) {
				$this->updateResources($base, $playerBonus);
				$this->updateAntiSpy($base);
			}

			$this->orbitalBaseRepository->save($base);
		}
		$this->entityManager->commit();
	}

	protected function updateResources(OrbitalBase $orbitalBase, PlayerBonus $playerBonus): void
	{
		$addResources = Game::resourceProduction(
			$this->orbitalBaseHelper->getBuildingInfo(
				OrbitalBaseResource::REFINERY,
				'level',
				$orbitalBase->levelRefinery,
				'refiningCoefficient'
			),
			$orbitalBase->place->coefResources,
		);
		$addResources += $addResources * $playerBonus->bonuses->get(PlayerBonusId::REFINERY_REFINING) / 100;

		$this->orbitalBaseManager->increaseResources($orbitalBase, (int) $addResources, false, false);
	}

	protected function updateAntiSpy(OrbitalBase $orbitalBase): void
	{
		$orbitalBase->antiSpyAverage = round((($orbitalBase->antiSpyAverage * (24 - 1)) + $orbitalBase->iAntiSpy) / 24);
	}
}
