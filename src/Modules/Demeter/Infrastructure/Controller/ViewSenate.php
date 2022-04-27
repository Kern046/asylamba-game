<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewSenate extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        ColorManager $colorManager,
        LawManager $lawManager,
    ): Response {
        if (!$currentPlayer->isParliamentMember()) {
            throw $this->createAccessDeniedException('You must be a parliament member');
        }

        if (null === ($faction = $colorManager->get($currentPlayer->getRColor()))) {
            throw $this->createNotFoundException('Faction not found');
        }

        return $this->render('pages/demeter/faction/senate.html.twig', [
            'faction' => $faction,
            'voting_laws' => $lawManager->getByFactionAndStatements($faction->getId(), [Law::VOTATION]),
            'voted_laws' => $lawManager->getByFactionAndStatements($faction->getId(), [Law::EFFECTIVE, Law::OBSOLETE, Law::REFUSED]),
        ]);
    }
}
