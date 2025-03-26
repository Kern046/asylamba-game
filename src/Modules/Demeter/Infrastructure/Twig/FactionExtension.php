<?php

namespace App\Modules\Demeter\Infrastructure\Twig;

use App\Modules\Demeter\Domain\Repository\Law\VoteLawRepositoryInterface;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Shared\Domain\Service\GameTimeConverter;
use App\Modules\Zeus\Model\Player;
use App\Shared\Application\Handler\DurationHandler;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FactionExtension extends AbstractExtension
{
	public function __construct(
		private readonly DurationHandler $durationHandler,
		private readonly GameTimeConverter $gameTimeConverter,
		private readonly VoteLawRepositoryInterface $voteLawRepository,
	) {
	}

	#[\Override]
    public function getFunctions(): array
	{
		return [
			// @TODO move get_faction_info here and replace these methods
			new TwigFunction('get_faction_statuses', fn (int $factionId) => $this->getFactionStatuses($factionId)),
			new TwigFunction('get_faction_name', fn (int $factionId) => $this->getFactionName($factionId)),
			new TwigFunction('get_faction_bonuses', fn (int $factionIdentifier) => ColorResource::getInfo($factionIdentifier, 'bonus')),
			new TwigFunction('get_law_info', fn (int $lawType, string $info) => LawResources::getInfo($lawType, $info)),
			new TwigFunction('has_voted_law', fn (Law $law, Player $player) => $this->voteLawRepository->hasVoted($player, $law)),
			new TwigFunction('get_law_duration', function (Law $law): int {
				$seconds = $this->durationHandler->getDiff($law->voteEndedAt, $law->endedAt);

				return $this->gameTimeConverter->convertSecondsToGameCycles($seconds);
			}),
			new TwigFunction('get_bonus_text', fn (int $bonusIdentifier) => ColorResource::getBonus($bonusIdentifier)),
		];
	}

	protected function getFactionStatuses(int $factionId): array
	{
		return ColorResource::getInfo($factionId, 'status');
	}

	protected function getFactionName(int $factionId): string
	{
		return ColorResource::getInfo($factionId, 'popularName');
	}
}
