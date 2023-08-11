<?php

namespace App\Modules\Demeter\Application\Election;

use App\Modules\Demeter\Domain\Repository\Election\ElectionRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Demeter\Resource\ColorResource;
use App\Shared\Application\Handler\DurationHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function Sodium\add;

class NextElectionDateCalculator
{
	public function __construct(
		private readonly ElectionRepositoryInterface $electionRepository,
		private readonly DurationHandler $durationHandler,
		#[Autowire('%server_start_time%')]
		private readonly string $serverStartTime,
	) {

	}

	public function getBallotDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction, Color::ELECTIONTIME + Color::CAMPAIGNTIME);
	}

	public function getSenateUpdateMessage(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction);
	}

	public function getNextElectionDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction, addMandateDuration: false);
	}

	public function getStartDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction, Color::ELECTIONTIME, false);
	}

	public function getEndDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction);
	}

	public function getPutschEndDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction, Color::PUTSCHTIME, false);
	}

	public function getCampaignStartDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction);
	}

	public function getCampaignEndDate(Color $faction): \DateTimeImmutable
	{
		return $this->calculate($faction, Color::CAMPAIGNTIME);
	}

	private function calculate(Color $faction, int $duration = 0, bool $addMandateDuration = true): \DateTimeImmutable
	{
		$lastElection = $this->electionRepository->getFactionLastElection($faction);

		if ($addMandateDuration) {
			$duration += ColorResource::getInfo($faction->identifier, 'mandateDuration');
		}

		return $this->durationHandler->getDurationEnd($lastElection->dElection ?? new \DateTimeImmutable($this->serverStartTime), $duration);
	}
}
