<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ViewEmbassy extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        ColorManager $colorManager,
        OrbitalBaseManager $orbitalBaseManager,
        PlayerManager $playerManager,
    ): Response {
        $data = [];

        if (null !== ($playerId = $request->query->get('player'))) {
            if (null === ($player = $playerManager->get($playerId)) || !in_array($player->getStatement(), [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY, Player::BANNED])) {
                throw new NotFoundHttpException('Player not found');
            }

            $data = [
                'player' => $player,
                'player_bases' => $orbitalBaseManager->getPlayerBases($playerId),
                'is_current_player' => $playerId === $currentPlayer->getId(),
            ];
        }

        if (null !== ($factionId = $request->query->get('faction')) || null === $playerId) {
            $factionId ??= $currentPlayer->getRColor();

            if (($faction = $colorManager->get($factionId)) !== null && $faction->isInGame) {
                $data = [
                    'faction' => $faction,
                    'parsed_description' => $colorManager->getParsedDescription($faction),
                    'government_members' => $playerManager->getGovernmentMembers($faction->id),
                    'diplomacy_statements' => [
                        Color::ENEMY => 'En guerre',
                        Color::ALLY => 'Allié',
                        Color::PEACE => 'Pacte de non-agression',
                        Color::NEUTRAL => 'Neutre',
                    ],
                ];
            } else {
                throw new NotFoundHttpException('Faction not found');
            }
        }

        return $this->render('pages/demeter/embassy.html.twig', array_merge($data, ['factions' => $colorManager->getInGameFactions()]));
    }
}
