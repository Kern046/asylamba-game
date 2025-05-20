<?php

declare(strict_types=1);

namespace App\Modules\Athena\Handler\Base;

use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Message\Base\BasesUpdateMessage;
use App\Modules\Athena\Message\Base\BaseUpdateMessage;
use App\Modules\Zeus\Infrastructure\Validator\IsPlayerAlive;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class BasesUpdateHandler
{
	public function __construct(
		private MessageBusInterface $messageBus,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
	) {
	}

	public function __invoke(BasesUpdateMessage $message): void
	{
		$bases = $this->orbitalBaseRepository->getBySpecification(new IsPlayerAlive());

		foreach ($bases as $base) {
			$this->messageBus->dispatch(new BaseUpdateMessage($base->id));
		}
	}
}
