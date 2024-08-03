<?php

namespace App\Classes\Library;

use App\Shared\Application\Handler\DurationHandler;

class Utils
{
	private static $autorizedChar = [
		'0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
		'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
	];

	public static function isAdmin($bindkey): bool
	{
		return in_array($bindkey, [
			'player1', 'player2', 'gil', 'noe', 'jacky',
			'YNbrdEaJpDw8mLZ2u6jYqgt6a',
			'jq2Bjf0uKzzE0aMErO6rpBxcg',
			'E6GatZvhO1W9seBHU7mgQe49c',
			'FaDcTV3lWokXHZO8tXH4divWZ',
			'KD6wb29ElI6sxVVtVoLJY0BbO',
			'nEYzsAcZYv',
			'xQTjMBnqbk6rO4ysglCJxLL63',
			'Dcc8VXaEeG6nQ49ZdkD2HusQi',
		]);
	}

	/**
	 * @deprecated use {@see \DateTimeImmutable} instead
	 */
	public static function now(): string
	{
		return date('Y-m-d H:i:s');
	}

	/**
	 * @deprecated Use {@see DurationHandler::getHoursDiff()} instead
	 */
	public static function interval($date1, $date2, $precision = 'h')
	{
		if ('h' == $precision) {
			$date1 = explode(' ', $date1);
			$hour1 = explode(':', $date1[1]);
			$newDate1 = $date1[0].' '.$hour1[0].':00:00';
			$time1 = strtotime($newDate1) / 3600;

			$date2 = explode(' ', $date2);
			$hour2 = explode(':', $date2[1]);
			$newDate2 = $date2[0].' '.$hour2[0].':00:00';
			$time2 = strtotime($newDate2) / 3600;
			$interval = abs($time1 - $time2);

			return $interval;
		} elseif ('s' == $precision) {
			$time1 = strtotime($date1);
			$time2 = strtotime($date2);

			$interval = abs($time1 - $time2);

			return $interval;
		}
	}

	/**
	 * @deprecated Use {@see DurationHandler::getHoursDiff()} instead
	 */
	public static function intervalDates($date1, $date2, $precision = 'h')
	{
		// give each full hours between two dates
		$dates = [];

		$baseDate = ($date1 < $date2) ? $date1 : $date2;
		$endDate = ($date1 < $date2) ? $date2 : $date1;

		if ('h' == $precision) {
			$baseTmst = strtotime($baseDate);
			$tail = new \DateTime($endDate);
			$cursor = new \DateTime(
				date('Y', $baseTmst).'-'.
				date('m', $baseTmst).'-'.
				date('d', $baseTmst).' '.
				date('H', $baseTmst).':00:00'
			);

			while (true) {
				$cursor->add(\DateInterval::createFromDateString('1 hour'));

				if ($cursor->getTimestamp() <= $tail->getTimestamp()) {
					$dates[] = $cursor->format('Y-m-d H:i:s');
				} else {
					break;
				}
			}
		} elseif ('d' == $precision) {
			// the changement is at 01:00:00
			$daysInterval = floor((abs(strtotime($date1) - strtotime($date2))) / (60 * 60 * 24));

			$seconds = strtotime($baseDate) + 86400;
			$nextDay = floor($seconds / 86400) * 86400;
			$fullDay = date('Y-m-d H:i:s', $nextDay);

			for ($i = 0; $i < $daysInterval; ++$i) {
				// add date to array
				$dates[] = $fullDay;
				// compute next date
				$newTime = strtotime($fullDay) + 86400;
				$fullDay = date('Y-m-d H:i:s', $newTime);
			}
			// if there is an hour change at the end
			if ($fullDay < $endDate) {
				$dates[] = $fullDay;
			}
		}

		return $dates;
	}

	/**
	 * @see DurationHandler::getDurationEnd()
	 * @deprecated Use \DateTimeImmutable instead
	 */
	public static function addSecondsToDate($date, $seconds)
	{
		return date('Y-m-d H:i:s', strtotime($date) + $seconds);
	}

	public static function generateString($nbr)
	{
		$password = '';
		for ($i = 0; $i < $nbr; ++$i) {
			$aleaChar = self::$autorizedChar[rand(0, count(self::$autorizedChar) - 1)];
			$password .= $aleaChar;
		}

		return $password;
	}

	public static function check($string, $mode = '')
	{
		$string = trim($string);
		$string = htmlspecialchars($string);
		if ('complex' == $mode) {
			$string = nl2br($string);
			$string = preg_replace('`http://[a-z0-9._,;/?!&=#-]+`i', '<a href="$0" target="blank">$0</a>', $string);
		}

		return $string;
	}

	public static function shuffle(&$array)
	{
		$keys = array_keys($array);

		shuffle($keys);

		foreach ($keys as $key) {
			$new[$key] = $array[$key];
		}

		$array = $new;

		return true;
	}
}
