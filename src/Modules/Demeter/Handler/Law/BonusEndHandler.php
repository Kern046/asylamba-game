<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Message\Law\BonusEndMessage;
use App\Modules\Demeter\Model\Law\Law;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class BonusEndHandler
{
	public function __construct(
		private LawRepositoryInterface $lawRepository,
	) {
	}

	public function __invoke(BonusEndMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId());
		$law->statement = Law::OBSOLETE;
		$this->lawRepository->save($law);
	}
}
