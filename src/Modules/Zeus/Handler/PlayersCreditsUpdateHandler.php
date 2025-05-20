<?php

namespace App\Modules\Zeus\Handler;

use App\Modules\Zeus\Domain\Message\PlayerCreditUpdateMessage;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Message\PlayersCreditsUpdateMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class PlayersCreditsUpdateHandler
{
	public function __construct(
		private PlayerRepositoryInterface $playerRepository,
		private MessageBusInterface $messageBus,
	) {
	}

	public function __invoke(PlayersCreditsUpdateMessage $message): void
	{
		$players = $this->playerRepository->getActivePlayers();

		foreach ($players as $player) {
			$this->messageBus->dispatch(new PlayerCreditUpdateMessage($player->id));
		}
	}
}
