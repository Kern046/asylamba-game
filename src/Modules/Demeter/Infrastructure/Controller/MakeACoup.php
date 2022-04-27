<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\DateTimeConverter;
use App\Classes\Library\Utils;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Manager\Election\CandidateManager;
use App\Modules\Demeter\Manager\Election\ElectionManager;
use App\Modules\Demeter\Manager\Election\VoteManager;
use App\Modules\Demeter\Manager\Forum\ForumTopicManager;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

class MakeACoup extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
        CandidateManager $candidateManager,
        ColorManager $colorManager,
        NotificationManager $notificationManager,
        PlayerManager $playerManager,
        VoteManager $voteManager,
        ElectionManager $electionManager,
        ForumTopicManager $forumTopicManager,
        EntityManager $entityManager,
        MessageBusInterface $messageBus,
    ): Response {
        $program = $request->request->get('program');
        $chiefChoice = $request->request->get('chiefchoice');
        $treasurerChoice = $request->request->get('treasurerchoice');
        $warlordChoice = $request->request->get('warlordchoice');
        $ministerChoice = $request->request->get('ministerchoice');

        if (false !== $program) {
            if ($currentPlayer->isParliamentMember()) {
                $faction = $colorManager->get($currentPlayer->getRColor());

                if (Color::MANDATE === $faction->electionStatement) {
                    if (Color::ROYALISTIC == $faction->regime) {
                        $election = new Election();
                        $election->rColor = $faction->id;
                        $election->dElection = (new \DateTime('+'.Color::PUTSCHTIME.' second'))->format('Y-m-d H:i:s');

                        $electionManager->add($election);

                        $candidate = new Candidate();
                        $candidate->rElection = $election->id;
                        $candidate->rPlayer = $currentPlayer->getId();
                        $candidate->chiefChoice = $chiefChoice;
                        $candidate->treasurerChoice = $treasurerChoice;
                        $candidate->warlordChoice = $warlordChoice;
                        $candidate->ministerChoice = $ministerChoice;
                        $candidate->dPresentation = Utils::now();
                        $candidate->program = $program;
                        $candidateManager->add($candidate);

                        $topic = new ForumTopic();
                        $topic->title = 'Candidat '.$currentPlayer->getName();
                        $topic->rForum = 30;
                        $topic->rPlayer = $candidate->rPlayer;
                        $topic->rColor = $faction->getId();
                        $topic->dCreation = Utils::now();
                        $topic->dLastMessage = Utils::now();
                        $forumTopicManager->add($topic);

                        $faction->electionStatement = Color::ELECTION;
                        $faction->dLastElection = Utils::now();

                        $vote = new Vote();
                        $vote->rPlayer = $currentPlayer->getId();
                        $vote->rCandidate = $currentPlayer->getId();
                        $vote->rElection = $election->id;
                        $vote->dVotation = Utils::now();
                        $voteManager->add($vote);

                        $factionPlayers = $playerManager->getFactionPlayers($faction->id);

                        foreach ($factionPlayers as $factionPlayer) {
                            if (Player::ACTIVE !== $factionPlayer->getStatement()) {
                                continue;
                            }
                            $notif = new Notification();
                            $notif->setRPlayer($factionPlayer->id);
                            $notif->setTitle('Coup d\'Etat.');
                            $notif->addBeg()
                                ->addTxt('Un membre de votre Faction soulève une partie du peuple et tente un coup d\'état contre le gouvernement.')
                                ->addSep()
                                ->addLnk('faction/view-election', 'prendre parti sur le coup d\'état.')
                                ->addEnd();
                            $notificationManager->add($notif);
                        }
                        $this->addFlash('success', 'Coup d\'état lancé.');
                        $messageBus->dispatch(
                            new BallotMessage($faction->getId()),
                            [DateTimeConverter::to_delay_stamp($election->dElection)],
                        );
                        $entityManager->flush();

                        return $this->redirect($request->headers->get('referer'));
                    } else {
                        throw new ErrorException('Vous vivez dans une faction démocratique.');
                    }
                } else {
                    throw new ErrorException('Un coup d\'état est déjà en cours.');
                }
            } else {
                throw new ErrorException('Vous ne pouvez pas vous présenter, vous ne faite pas partie de l\'élite ou vous êtes déjà le hef de la faction.');
            }
        } else {
            throw new ErrorException('Informations manquantes.');
        }
    }
}
