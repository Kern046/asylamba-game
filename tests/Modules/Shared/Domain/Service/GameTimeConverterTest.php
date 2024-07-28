<?php

namespace App\Tests\Modules\Shared\Domain\Service;

use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Shared\Domain\Service\GameTimeConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GameTimeConverterTest extends TestCase
{
	#[DataProvider('provideData')]
	public function testConvertSecondsToGameCycles(int $seconds, int $gameCycles, TimeMode $timeMode): void
	{
		$gameTimeConverter = new GameTimeConverter($timeMode);

		static::assertSame($gameCycles, $gameTimeConverter->convertSecondsToGameCycles($seconds));
	}

	#[DataProvider('provideData')]
	public function testConvertGameCyclesToSeconds(int $seconds, int $gameCycles, TimeMode $timeMode): void
	{
		$gameTimeConverter = new GameTimeConverter($timeMode);

		static::assertSame($seconds, $gameTimeConverter->convertGameCyclesToSeconds($gameCycles));
	}

	public static function provideData(): \Generator
	{
		yield [
			3600,
			1,
			TimeMode::Standard,
		];

		yield [
			600,
			1,
			TimeMode::Fast,
		];

		yield [
			3600,
			6,
			TimeMode::Fast,
		];

		yield [
			7200,
			2,
			TimeMode::Standard,
		];
	}
}
