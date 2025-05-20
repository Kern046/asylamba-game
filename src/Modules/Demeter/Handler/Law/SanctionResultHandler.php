<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Message\Law\SanctionResultMessage;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SanctionResultHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private LawRepositoryInterface $lawRepository,
		private PlayerManager $playerManager,
		private PlayerRepositoryInterface $playerRepository,
	) {
	}

	public function __invoke(SanctionResultMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId());
		$color = $law->faction;
		$player = $this->playerRepository->get($law->options['rPlayer']);

		$toPay = $law->options['credits'];
		if ($player->credit < $law->options['credits']) {
			$toPay = $player->credit;
		}
		$this->playerManager->decreaseCredit($player, $toPay);
		$color->credits += $toPay;
		$law->statement = Law::OBSOLETE;
		$this->colorRepository->save($color);
		$this->lawRepository->save($law);
	}
}
