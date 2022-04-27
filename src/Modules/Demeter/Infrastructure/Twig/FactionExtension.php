<?php

namespace App\Modules\Demeter\Infrastructure\Twig;

use App\Modules\Demeter\Manager\Law\VoteLawManager;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Zeus\Model\Player;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FactionExtension extends AbstractExtension
{
    public function __construct(
        protected VoteLawManager $voteLawManager,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            // @TODO move get_faction_info here and replace these methods
            new TwigFunction('get_faction_statuses', fn (int $factionId) => $this->getFactionStatuses($factionId)),
            new TwigFunction('get_faction_name', fn (int $factionId) => $this->getFactionName($factionId)),
            new TwigFunction('get_law_info', fn (int $lawType, string $info) => LawResources::getInfo($lawType, $info)),
            new TwigFunction('has_voted_law', fn (Law $law, Player $player) => $this->voteLawManager->hasVoted($player->getId(), $law)),
            new TwigFunction('get_law_duration', fn (Law $law) => max((strtotime($law->dEnd) - strtotime($law->dEndVotation)) / 3600, 1)),
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
