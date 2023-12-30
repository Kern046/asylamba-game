<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig;

use App\Classes\Library\Game;
use App\Modules\Ares\Application\Handler\CommanderArmyHandler;
use App\Modules\Ares\Application\Handler\CommanderExperienceHandler;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Resource\CommanderResources;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CommanderExtension extends AbstractExtension
{
	public function __construct(
		private readonly CommanderManager $commanderManager,
		private readonly CommanderExperienceHandler $commanderExperienceHandler,
		private readonly CommanderArmyHandler $commanderArmyHandler,
	) {
	}

	public function getFilters(): array
	{
		return [
			new TwigFilter('mission_label', fn (Commander $commander) => match ($commander->travelType) {
				Commander::MOVE => 'déplacement vers ' . $commander->destinationPlace->base?->name ?? 'colonie rebelle',
				Commander::LOOT => 'pillage de ' . $commander->destinationPlace->base?->name ?? 'colonie rebelle',
				Commander::COLO => 'colonisation de ' . $commander->destinationPlace->base?->name ?? 'colonie rebelle',
				Commander::BACK => 'retour vers ' . $commander->destinationPlace->base?->name ?? 'colonie rebelle',
				default => 'autre'
			}),
			new TwigFilter('commander_rank', fn (Commander $commander) => $this->getCommanderLevel($commander->level)),
			new TwigFilter('pev', fn (Commander $commander) => $this->commanderArmyHandler->getPev($commander)),
		];
	}

	public function getFunctions(): array
	{
		return [
			new TwigFunction('get_commander_level_up_from_report', fn (int $level, int $newExperience) => $this->commanderExperienceHandler->nbLevelUp($level, $newExperience)),
			new TwigFunction('get_commander_missing_experience', fn (Commander $commander) => $this->commanderExperienceHandler->experienceToLevelUp($commander)),
			new TwigFunction('get_fleet_cost', fn (Commander $commander) => Game::getFleetCost($commander->getNbrShipByType())),
			new TwigFunction('get_commander_position', fn (Commander $commander, int $x1, int $x2, int $y1, int $y2) => $this->commanderManager->getPosition($commander, $x1, $x2, $x2, $y2)),
			new TwigFunction('get_commander_rank', fn (int $level) => $this->getCommanderLevel($level)),
			new TwigFunction('get_commander_price', fn (Commander $commander, float $commanderCurrentRate) => intval(ceil($commander->experience * $commanderCurrentRate))),
		];
	}

	protected function getCommanderLevel(int $level): string
	{
		return CommanderResources::getInfo($level, 'grade');
	}
}
