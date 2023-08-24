<?php

namespace App\Classes\Scheduler;

use App\Modules\Ares\Message\CommandersSchoolExperienceMessage;
use App\Modules\Athena\Message\Base\BasesUpdateMessage;
use App\Modules\Gaia\Message\PlacesUpdateMessage;
use App\Modules\Gaia\Message\PlayersPlacesUpdateMessage;
use App\Modules\Hephaistos\Message\DailyRoutineMessage;
use App\Modules\Zeus\Message\PlayersCreditsUpdateMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class CyclicActionScheduler
{
	/** @var array<string, list<class-string>> * */
	protected array $queues = [
		self::TYPE_DAILY => [
			DailyRoutineMessage::class,
		],
		self::TYPE_HOURLY => [
			BasesUpdateMessage::class,
			CommandersSchoolExperienceMessage::class,
			PlacesUpdateMessage::class,
			PlayersCreditsUpdateMessage::class,
		],
	];

	public const TYPE_HOURLY = 'hourly';
	public const TYPE_DAILY = 'daily';

	public function __construct(
		protected MessageBusInterface $messageBus,
		protected int $dailyScriptHour
	) {
	}

	public function executeHourlyTasks(): void
	{
		$this->processQueue(self::TYPE_HOURLY);
	}

	public function executeDailyTasks(): void
	{
		$this->processQueue(self::TYPE_DAILY);
	}

	protected function processQueue(string $queue): void
	{
		foreach ($this->queues[$queue] as $messageClass) {
			$this->messageBus->dispatch(new $messageClass());
		}
	}
}
