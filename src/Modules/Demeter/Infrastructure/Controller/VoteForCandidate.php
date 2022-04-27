<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Exception\ErrorException;
use App\Classes\Library\Utils;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Manager\Election\CandidateManager;
use App\Modules\Demeter\Manager\Election\ElectionManager;
use App\Modules\Demeter\Manager\Election\VoteManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VoteForCandidate extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        ColorManager $colorManager,
        VoteManager $voteManager,
        PlayerManager $playerManager,
        ElectionManager $electionManager,
        CandidateManager $candidateManager,
        int $electionId,
        int $candidateId,
    ): Response {
        $leader = $playerManager->getFactionLeader($currentPlayer->getRColor());

        if (0 == $candidateId) {
            $candidateId = $leader->id;
        }

        if (($election = $electionManager->get($electionId)) !== null) {
            if (($candidateManager->getByElectionAndPlayer($election, $playerManager->get($candidateId))) !== null || $leader->id == $candidateId) {
                if ($election->rColor == $currentPlayer->getRColor()) {
                    if (($voteManager->getPlayerVote($currentPlayer, $election)) === null) {
                        $faction = $colorManager->get($currentPlayer->getRColor());

                        if (Color::ELECTION == $faction->electionStatement) {
                            $vote = new Vote();
                            $vote->rPlayer = $currentPlayer->getId();
                            $vote->rCandidate = $candidateId;
                            $vote->rElection = $electionId;
                            $vote->dVotation = Utils::now();
                            $voteManager->add($vote);
                            $this->addFlash('success', 'Vous avez voté.');

                            return $this->redirect($request->headers->get('referer'));
                        } else {
                            throw new ErrorException('Vous ne pouvez voter pour un candidat qu\'en période d\'élection.');
                        }
                    } else {
                        throw new ErrorException('Vous avez déjà voté.');
                    }
                } else {
                    throw new ErrorException('Cette election ne se déroule pas dans votre faction.');
                }
            } else {
                throw new ErrorException('Ce candidat n\'existe pas.');
            }
        } else {
            throw new ErrorException('Cette election n\'existe pas.');
        }
    }
}
