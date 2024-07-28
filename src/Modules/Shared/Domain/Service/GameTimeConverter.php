<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Service;

use App\Modules\Shared\Domain\Server\TimeMode;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class GameTimeConverter
{
	private const GAME_CYCLE_SECONDS_FAST_MODE = 600;
	private const GAME_CYCLE_SECONDS_STANDARD_MODE = 3600;

	private const GAME_CYCLE_SECONDS = [
		TimeMode::Standard->name => self::GAME_CYCLE_SECONDS_STANDARD_MODE,
		TimeMode::Fast->name => self::GAME_CYCLE_SECONDS_FAST_MODE,
	];


	public function __construct(
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {

	}

	public function convertSecondsToGameCycles(int $seconds): int
	{
		return intval(round($seconds / self::GAME_CYCLE_SECONDS[$this->timeMode->name]));
	}

	public function convertGameCyclesToSeconds(int $gameCycles): int
	{
		return self::GAME_CYCLE_SECONDS[$this->timeMode->name] * $gameCycles;
	}
}
