<?php

namespace App\Classes\Library;

use Symfony\Component\Messenger\Stamp\DelayStamp;

class DateTimeConverter
{
	public static function to_delay_stamp(\DateTimeImmutable $dateTime): DelayStamp
	{
		return new DelayStamp(self::to_ms($dateTime));
	}

	public static function to_ms(\DateTimeImmutable $dateTime): int
	{
		$diff = $dateTime->getTimestamp() - time();

		return ($diff > 0) ? $diff * 1000 : 0;
	}
}
