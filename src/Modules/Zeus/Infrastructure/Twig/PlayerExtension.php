<?php

namespace App\Modules\Zeus\Infrastructure\Twig;

use App\Classes\Library\Utils;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Helper\ResearchHelper;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PlayerExtension extends AbstractExtension
{
	public function __construct(
		private readonly BonusApplierInterface $bonusApplier,
		private readonly CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		private readonly OrbitalBaseManager $orbitalBaseManager,
		private readonly ResearchHelper $researchHelper,
		private readonly int $timeEventUpdate,
		private readonly int $allyInactiveTime,
	) {
	}

	public function getFunctions(): array
	{
		return [
			new TwigFunction('apply_player_bonus', fn (int|float $initialValue, int $playerBonusId) => $this->bonusApplier->apply($initialValue, $playerBonusId)),
			new TwigFunction('get_player_bonus', fn (int $playerBonusId) => $this->currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get($playerBonusId)),
			new TwigFunction('get_faction_info', fn (int $factionId, string $info) => ColorResource::getInfo($factionId, $info)),
			new TwigFunction('get_player_bases_count', fn (array $movingCommanders) => $this->orbitalBaseManager->getPlayerBasesCount($movingCommanders)),
			new TwigFunction('get_research_info', fn (string $researchType, string $info) => $this->researchHelper->getInfo($researchType, $info)),
			new TwigFunction('is_player_online', fn (Player $player) => Utils::interval(Utils::now(), $player->dLastActivity, 's') < ($this->timeEventUpdate * 2)),
			new TwigFunction('is_player_inactive', fn (Player $player) => Utils::interval(Utils::now(), $player->dLastActivity) > $this->allyInactiveTime),
		];
	}
}
