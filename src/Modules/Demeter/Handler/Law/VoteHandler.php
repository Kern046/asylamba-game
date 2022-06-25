<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Classes\Library\Utils;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Message\Law\VoteMessage;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\LawResources;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class VoteHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private LawRepositoryInterface $lawRepository,
		private LawManager $lawManager,
	) {
	}

	public function __invoke(VoteMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId());
		$faction = $law->faction;
		$ballot = $this->lawManager->ballot($law);
		if ($ballot) {
			// accepter la loi
			$law->statement = Law::EFFECTIVE;
		// envoyer un message
		} else {
			// refuser la loi
			$law->statement = Law::REFUSED;
			if (LawResources::getInfo($law->type, 'bonusLaw')) {
				$faction->credits += (LawResources::getInfo($law->type, 'price') * Utils::interval($law->dEndVotation, $law->dEnd) * ($faction->activePlayers + 1) * 90) / 100;
			} else {
				$faction->credits += (LawResources::getInfo($law->type, 'price') * 90) / 100;
			}
			// envoyer un message
		}
		$this->lawRepository->save($law);
		$this->colorRepository->save($faction);
	}
}
