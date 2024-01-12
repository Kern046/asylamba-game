<?php

namespace App\Modules\Demeter\Handler;

use App\Classes\Library\DateTimeConverter;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\ElectionRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Message\ElectionMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Election;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class CampaignHandler
{
	public function __construct(
		private ColorManager $colorManager,
		private ColorRepositoryInterface $colorRepository,
		private ElectionRepositoryInterface $electionRepository,
		private MessageBusInterface $messageBus,
		private NextElectionDateCalculator $nextElectionDateCalculator,
	) {
	}

	public function __invoke(CampaignMessage $message): void
	{
		$faction = $this->colorRepository->get($message->getFactionId())
			?? throw new \RuntimeException(sprintf('Faction %s not found', $message->getFactionId()));

		$this->colorManager->updateStatus($faction);

		$election = new Election(
			id: Uuid::v4(),
			faction: $faction,
			dElection: $this->nextElectionDateCalculator->getCampaignEndDate($faction),
		);

		$this->electionRepository->save($election);

		$faction->electionStatement = Color::CAMPAIGN;
		if ($faction->isDemocratic()) {
			$this->messageBus->dispatch(
				new ElectionMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($election->dElection)],
			);
		} elseif ($faction->isTheocratic()) {
			$this->messageBus->dispatch(
				new BallotMessage($faction->id),
				[DateTimeConverter::to_delay_stamp($election->dElection)],
			);
		}
		$this->electionRepository->save($election);
	}
}
