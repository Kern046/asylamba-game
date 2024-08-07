<?php

declare(strict_types=1);

namespace App\Modules\Athena\Handler\Base;

use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Message\Base\BasesUpdateMessage;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Shared\Application\Service\CountMissingSystemUpdates;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsPlayerAlive;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;
use App\Shared\Application\Handler\DurationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class BasesUpdateHandler
{
	public function __construct(
		private ClockInterface $clock,
		private BonusApplierInterface $bonusApplier,
		private EntityManagerInterface $entityManager,
		private PlayerBonusManager $playerBonusManager,
		private CountMissingSystemUpdates $countMissingSystemUpdates,
		private OrbitalBaseManager $orbitalBaseManager,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private OrbitalBaseHelper $orbitalBaseHelper,
	) {
	}

	public function __invoke(BasesUpdateMessage $message): void
	{
		$bases = $this->orbitalBaseRepository->getBySpecification(new IsPlayerAlive());
		$this->entityManager->beginTransaction();

		foreach ($bases as $base) {
			$missingUpdatesCount = ($this->countMissingSystemUpdates)($base);
			if (0 === $missingUpdatesCount) {
				continue;
			}

			$playerBonus = $this->playerBonusManager->getBonusByPlayer($base->player);
			$base->updatedAt = $this->clock->now();

			for ($i = 0; $i < $missingUpdatesCount; ++$i) {
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
		$addResources += $this->bonusApplier->apply($addResources, PlayerBonusId::REFINERY_REFINING, $playerBonus);

		$this->orbitalBaseManager->increaseResources($orbitalBase, intval(round($addResources)), false);
	}

	protected function updateAntiSpy(OrbitalBase $orbitalBase): void
	{
		$orbitalBase->antiSpyAverage = intval(round((($orbitalBase->antiSpyAverage * (24 - 1)) + $orbitalBase->iAntiSpy) / 24));
	}
}
