<?php

declare(strict_types=1);

namespace App\Modules\Athena\Handler\Base;

use App\Classes\Library\Game;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Message\Base\BaseUpdateMessage;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Shared\Application\Service\CountMissingSystemUpdates;
use App\Modules\Shared\Domain\Service\GameTimeConverter;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class BaseUpdateHandler
{
	private const int MAX_UPDATES = 10;
	
	public function __construct(
		private GameTimeConverter $gameTimeConverter,
		private BonusApplierInterface $bonusApplier,
		private EntityManagerInterface $entityManager,
		private PlayerBonusManager $playerBonusManager,
		private CountMissingSystemUpdates $countMissingSystemUpdates,
		private OrbitalBaseManager $orbitalBaseManager,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private OrbitalBaseHelper $orbitalBaseHelper,
		private MessageBusInterface $messageBus,
		private LoggerInterface $logger,
	) {
	}

	public function __invoke(BaseUpdateMessage $message): void
	{
		$base = $this->orbitalBaseRepository->get($message->baseId)
			?? throw new \RuntimeException(sprintf('Base %s not found', $message->baseId));

		$missingUpdatesCount = ($this->countMissingSystemUpdates)($base);
		if (0 === $missingUpdatesCount) {
			return;
		}

		$playerBonus = $this->playerBonusManager->getBonusByPlayer($base->player);

		$secondsPerGameCycle = $this->gameTimeConverter->convertGameCyclesToSeconds(1);

		try {
			$this->entityManager->beginTransaction();

			$launchNewMessage = false;

			for ($i = 0; $i < $missingUpdatesCount; ++$i) {
				if ($i === self::MAX_UPDATES) {
					$launchNewMessage = true;

					break;
				}
				$this->updateResources($base, $playerBonus);
				$this->updateAntiSpy($base);

				$base->updatedAt = $base->updatedAt->modify(sprintf('+%d seconds', $secondsPerGameCycle));
			}
	
			$this->orbitalBaseRepository->save($base);
	
			$this->entityManager->commit();
	
			if (true === $launchNewMessage) {
				$this->messageBus->dispatch(new BaseUpdateMessage($base->id));
	
				$this->logger->debug('Dispatched new base update message for the next iterations for base {baseName} of player {playerName}', [
					'baseName' => $base->name,
					'baseId' => $base->id,
					'playerName' => $base->player->name,
					'playerId' => $base->player->id,
				]);
			}
		} catch (\Throwable $e) {
			$this->entityManager->rollback();
			
			throw $e;
		}
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
