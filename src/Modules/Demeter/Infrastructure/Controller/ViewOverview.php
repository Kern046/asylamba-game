<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\Format;
use App\Classes\Library\Utils;
use App\Modules\Atlas\Manager\FactionRankingManager;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Manager\Forum\FactionNewsManager;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ViewOverview extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        ColorManager $colorManager,
        FactionNewsManager $factionNewsManager,
        FactionRankingManager $factionRankingManager,
        PlayerManager $playerManager,
        LawManager $lawManager,
    ): Response {
        if (null === ($faction = $colorManager->get($currentPlayer->getRColor()))) {
            throw new NotFoundHttpException('Faction not found');
        }
        if ('list' === $request->query->get('news')) {
            $factionNews = $factionNewsManager->getFactionBasicNews($faction->id);
            $mode = 'all';
        } else {
            $factionNews = $factionNewsManager->getFactionPinnedNew($faction->id);
            $mode = 'pin';
        }

        $data = [
            'faction_ranking' => $this->getFactionRanking($factionRankingManager, $faction),
            'faction' => $faction,
            'news' => $factionNews,
            'news_mode' => $mode,
            'government_members' => $playerManager->getGovernmentMembers($faction->getId()),
            'effective_laws' => $lawManager->getByFactionAndStatements($faction->getId(), [Law::EFFECTIVE]),
            'voting_laws' => $lawManager->getByFactionAndStatements($faction->getId(), [Law::VOTATION]),
        ];

        if ($faction->hasElections()) {
            $data = array_merge($data, $this->getElectionsData($faction));
        } elseif ($faction->isInElection()) {
            $endPutsch = Utils::addSecondsToDate($faction->dLastElection, Color::PUTSCHTIME);

            $data['remaining_coup_time'] = Utils::interval(Utils::now(), $endPutsch, 's');
        }

        return $this->render('pages/demeter/faction/overview.html.twig', $data);
    }

    private function getFactionRanking(FactionRankingManager $factionRankingManager, Color $faction): int|null
    {
        foreach ($factionRankingManager->getAll() as $ranking) {
            if ($ranking->rFaction == $faction->id) {
                return $ranking->generalPosition;
            }
        }

        return null;
    }

    private function getElectionsData(Color $faction): array
    {
        // time variables
        $startCampaign = Utils::addSecondsToDate($faction->dLastElection, ColorResource::getInfo($faction->id, 'mandateDuration'));
        $endCampaign = Utils::addSecondsToDate($faction->dLastElection, ColorResource::getInfo($faction->id, 'mandateDuration') + Color::CAMPAIGNTIME);

        $startElection = Utils::addSecondsToDate($faction->dLastElection, ColorResource::getInfo($faction->id, 'mandateDuration') + Color::CAMPAIGNTIME);
        $endElection = Utils::addSecondsToDate($faction->dLastElection, ColorResource::getInfo($faction->id, 'mandateDuration') + Color::CAMPAIGNTIME + Color::ELECTIONTIME);

        $startMandate = $faction->dLastElection;
        $endMandate = Color::DEMOCRATIC == $faction->regime
            ? $endElection
            : $endCampaign;

        $totalCampaignElection = Utils::interval($startCampaign, $endElection, 's');
        $remainingCampaignElection = Utils::interval(Utils::now(), $startCampaign, 's');
        // @TODO Rename these keys to give more meaning
        return [
            'total_mandate' => Utils::interval($startMandate, $endMandate, 's'),
            'remaining_mandate' => Utils::interval(Utils::now(), $startMandate, 's'),
            'total_before_campaign' => Utils::interval($startMandate, $startCampaign, 's'),
            'total_campaign_election' => $totalCampaignElection,
            'total_campaign' => Utils::interval($startCampaign, $endCampaign, 's'),
            'remaining_campaign_election' => $remainingCampaignElection,
            'election_progress' => (Utils::now() > $startCampaign ? Format::percent($remainingCampaignElection, $totalCampaignElection, false) : '0'),
        ];
    }
}
