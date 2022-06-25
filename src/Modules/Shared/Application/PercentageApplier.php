<?php

namespace App\Modules\Shared\Application;

class PercentageApplier
{
	public static function toFloat(int|float $value, int|float $percent): float
	{
		return $value * $percent / 100;
	}

	public static function toInt(int|float $value, int|float $percent): int
	{
		return intval(floor(self::toFloat($value, $percent)));
	}
}
