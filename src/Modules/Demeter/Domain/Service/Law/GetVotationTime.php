<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Service\Law;

use App\Modules\Shared\Domain\Server\TimeMode;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class GetVotationTime
{
	public function __construct(
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function __invoke(): int
	{
		return match ($this->timeMode) {
			TimeMode::Standard => 86400,
			TimeMode::Fast => 3600,
		};
	}
}
