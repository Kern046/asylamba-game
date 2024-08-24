<?php

namespace App\Tests\Modules\Shared\Domain\Service;

use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Shared\Domain\Service\GameTimeConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameTimeConverterTest extends KernelTestCase
{
	#[DataProvider('provideGameCyclesConversionData')]
	public function testConvertSecondsToGameCycles(int $seconds, int $gameCycles, TimeMode $timeMode): void
	{
		$_ENV['SERVER_TIME_MODE'] = $timeMode->value;
		static::bootKernel();
		/** @var GameTimeConverter $gameTimeConverter */
		$gameTimeConverter = static::getContainer()->get(GameTimeConverter::class);

		static::assertSame($gameCycles, $gameTimeConverter->convertSecondsToGameCycles($seconds));
	}

	#[DataProvider('provideGameCyclesConversionData')]
	public function testConvertGameCyclesToSeconds(int $seconds, int $gameCycles, TimeMode $timeMode): void
	{
		$_ENV['SERVER_TIME_MODE'] = $timeMode->value;
		static::bootKernel();
		/** @var GameTimeConverter $gameTimeConverter */
		$gameTimeConverter = static::getContainer()->get(GameTimeConverter::class);

		static::assertSame($seconds, $gameTimeConverter->convertGameCyclesToSeconds($gameCycles));
	}

	#[DataProvider('provideGameDateConversionData')]
	public function testConvertDatetimeToGameDate(TimeMode $timeMode, string $serverStartDate, int $serverSegmentShift, string $currentDate, string $expectedGameDate): void
	{
		$_ENV['SERVER_TIME_MODE'] = $timeMode->value;
		$_ENV['SERVER_START_TIME'] = (new \DateTimeImmutable($serverStartDate))->format('Y-m-d H:i:s');
		$_ENV['SERVER_SEGMENT_SHIFT'] = $serverSegmentShift;
		static::bootKernel();
		/** @var GameTimeConverter $gameTimeConverter */
		$gameTimeConverter = static::getContainer()->get(GameTimeConverter::class);

		$gameDate = $gameTimeConverter->convertDatetimeToGameDate(new \DateTimeImmutable($currentDate), false);

		static::assertSame($expectedGameDate, $gameDate);
	}

	/**
	 * @return \Generator<array{
	 *     0: int,
	 *     1: int,
	 *     2: TimeMode,
	 * }>
	 */
	public static function provideGameCyclesConversionData(): \Generator
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

	public static function provideGameDateConversionData(): \Generator
	{
		yield [
			TimeMode::Standard,
			'-2 days',
			100,
			'+4 hours',
			'SEG102 REL4',
		];

		yield [
			TimeMode::Standard,
			'-2 days',
			50,
			'+36 hours',
			'SEG53 REL12',
		];

		yield [
			TimeMode::Fast,
			'-1 days',
			50,
			'+1 hour',
			'SEG56 REL6',
		];
	}
}
