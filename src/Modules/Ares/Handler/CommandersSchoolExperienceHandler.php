<?php

declare(strict_types=1);

namespace App\Modules\Ares\Handler;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Domain\Specification\CanEarnSchoolExperience;
use App\Modules\Ares\Message\CommanderSchoolExperienceMessage;
use App\Modules\Ares\Message\CommandersSchoolExperienceMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class CommandersSchoolExperienceHandler
{
	public function __construct(
		private CommanderRepositoryInterface $commanderRepository,
		private MessageBusInterface $messageBus,
	) {
	}

	public function __invoke(CommandersSchoolExperienceMessage $message): void
	{
		$commanders = $this->commanderRepository->getBySpecification(new CanEarnSchoolExperience());

		foreach ($commanders as $commander) {
			$this->messageBus->dispatch(new CommanderSchoolExperienceMessage($commander->id));
		}
	}
}
