<?php

namespace App\Modules\Demeter\Handler;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\ElectionRepositoryInterface;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Model\Color;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ElectionHandler
{
	public function __construct(
		private readonly ColorRepositoryInterface $colorRepository,
		private readonly NextElectionDateCalculator $nextElectionDateCalculator,
		private readonly MessageBusInterface $messageBus,
	) {
	}

	public function __invoke(ElectionMessage $message): void
	{
		$faction = $this->colorRepository->get($message->getFactionId());
		$faction->electionStatement = Color::ELECTION;

		$this->messageBus->dispatch(
			new BallotMessage($faction->id),
			[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getStartDate($faction))]
		);

		$this->colorRepository->save($faction);
	}
}
