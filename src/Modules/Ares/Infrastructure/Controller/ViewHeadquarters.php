<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Classes\Container\Params;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewHeadquarters extends AbstractController
{
    public function __invoke(
        Request $request,
        CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
        Player $currentPlayer,
        CommanderManager $commanderManager,
        OrbitalBaseManager $orbitalBaseManager,
    ): Response {
        // @TODO demistify that part
        if ($request->query->has('commander') && null !== ($commander = $commanderManager->get($request->query->get('commander')))) {
            if ($commander->rPlayer === $currentPlayer->getId() && in_array($commander->getStatement(), [Commander::AFFECTED, Commander::MOVING])) {
                $commanderBase = $orbitalBaseManager->get($commander->getRBase());
            } else {
                $commander = null;
            }
        }

        [$obsets, $commandersIds] = $this->getObsetsAndCommandersIds($request, $currentPlayer, $currentPlayerBasesRegistry, $commanderManager);

        return $this->render('pages/ares/fleet/headquarters.html.twig', [
            'commander' => $commander ?? null,
            'commander_base' => $commanderBase ?? null,
            'default_parameters' => Params::$params,
            'obsets' => $obsets,
            'commandersIds' => $commandersIds,
        ]);
    }

    private function getObsetsAndCommandersIds(
        Request $request,
        Player $currentPlayer,
        CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
        CommanderManager $commanderManager,
    ): array {
        $session = $request->getSession();
        $obsets = [];
        foreach ($currentPlayerBasesRegistry->all() as $orbitalBase) {
            if ($request->cookies->get('p'.Params::LIST_ALL_FLEET, Params::$params[Params::LIST_ALL_FLEET]) || $orbitalBase->getId() == $currentPlayerBasesRegistry->current()->getId()) {
                $obsets[] = [
                    'info' => [
                        'id' => $orbitalBase->getId(),
                        'name' => $orbitalBase->name,
                        'type' => $orbitalBase->typeOfBase,
                        'img' => $orbitalBase->img,
                    ],
                    'fleets' => [],
                ];
            }
        }

        // commander manager : incoming attack
        $commandersId = [0];
        for ($i = 0; $i < $session->get('playerEvent')->size(); ++$i) {
            if ($session->get('playerEvent')->get($i)->get('eventType') == $this->getParameter('event_incoming_attack')) {
                if ($session->get('playerEvent')->get($i)->get('eventInfo')->size() > 0) {
                    $commandersId[] = $session->get('playerEvent')->get($i)->get('eventId');
                }
            }
        }

        $attackingCommanders = $commanderManager->getVisibleIncomingAttacks($currentPlayer->id);
        for ($i = 0; $i < count($obsets); ++$i) {
            foreach ($attackingCommanders as $commander) {
                if ($commander->rDestinationPlace == $obsets[$i]['info']['id']) {
                    $obsets[$i]['fleets'][] = $commander;
                }
            }
        }
        $commanders = $commanderManager->getPlayerCommanders($currentPlayer->id, [Commander::AFFECTED, Commander::MOVING], ['c.rBase' => 'DESC']);

        for ($i = 0; $i < count($obsets); ++$i) {
            foreach ($commanders as $commander) {
                if ($commander->rBase == $obsets[$i]['info']['id']) {
                    $obsets[$i]['fleets'][] = $commander;
                }
            }
        }

        return [$obsets, $commandersId];
    }
}
