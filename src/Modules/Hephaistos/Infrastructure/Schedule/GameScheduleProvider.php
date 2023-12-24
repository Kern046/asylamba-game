<?php

declare(strict_types=1);

namespace App\Modules\Hephaistos\Infrastructure\Schedule;

use App\Modules\Ares\Message\CommandersSchoolExperienceMessage;
use App\Modules\Athena\Message\Base\BasesUpdateMessage;
use App\Modules\Gaia\Message\PlacesUpdateMessage;
use App\Modules\Hephaistos\Message\DailyRoutineMessage;
use App\Modules\Zeus\Message\PlayersCreditsUpdateMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('game')]
class GameScheduleProvider implements ScheduleProviderInterface
{
	public function getSchedule(): Schedule
	{
		$hourlySchedule = '0 * * * *';
		$nightlySchedule = '0 3 * * *';

		return (new Schedule())->add(
			// Hourly schedule
			RecurringMessage::cron($hourlySchedule, new PlacesUpdateMessage()),
			RecurringMessage::cron($hourlySchedule, new BasesUpdateMessage()),
			RecurringMessage::cron($hourlySchedule, new CommandersSchoolExperienceMessage()),
			RecurringMessage::cron($hourlySchedule, new PlayersCreditsUpdateMessage()),
			// Night schedule
			RecurringMessage::cron($nightlySchedule, new DailyRoutineMessage()),
		);
	}
}
