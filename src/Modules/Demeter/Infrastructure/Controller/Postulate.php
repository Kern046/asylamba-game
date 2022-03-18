<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Utils;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Manager\Election\CandidateManager;
use App\Modules\Demeter\Manager\Election\ElectionManager;
use App\Modules\Demeter\Manager\Election\VoteManager;
use App\Modules\Demeter\Manager\Forum\ForumTopicManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Postulate extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ElectionManager $electionManager,
		ColorManager $colorManager,
		CandidateManager $candidateManager,
		PlayerManager $playerManager,
		ForumTopicManager $forumTopicManager,
		VoteManager $voteManager,
		EntityManager $entityManager,
		int $id,
	): Response {
		$program			 = $request->request->get('program');
		$chiefChoice		 = $request->request->get('chiefchoice');
		$treasurerChoice	 = $request->request->get('treasurerchoice');
		$warlordChoice		 = $request->request->get('warlordchoice');
		$ministerChoice		 = $request->request->get('ministerchoice');

		if ($program !== FALSE) {
			if (($election = $electionManager->get($electionId)) !== null) {
				if ($election->rColor == $currentPlayer->getRColor()) {
					$chiefChoice = 1;
					$treasurerChoice = 1;
					$warlordChoice = 1;
					$ministerChoice = 1;

					if ($currentPlayer->isParliamentMember()) {
						$faction = $colorManager->get($currentPlayer->getRColor());

						if ($faction->electionStatement == Color::CAMPAIGN) {
							if ($chiefChoice !== NULL && $treasurerChoice !== FALSE && $warlordChoice !== FALSE && $ministerChoice !== FALSE) {
								if (($candidate = $candidateManager->getByElectionAndPlayer($election, $currentPlayer)) === null) {
									$candidate = new Candidate();

									$candidate->rElection = $electionId;
									$candidate->rPlayer = $currentPlayer->getId();
									$candidate->chiefChoice = $chiefChoice;
									$candidate->treasurerChoice = $treasurerChoice;
									$candidate->warlordChoice = $warlordChoice;
									$candidate->ministerChoice = $ministerChoice;
									$candidate->dPresentation = Utils::now();
									$candidate->program = $program;

									$candidateManager->add($candidate);

									$topic = new ForumTopic();
									$topic->title = 'Candidat ' . $currentPlayer->getName();
									$topic->rForum = 30;
									$topic->rPlayer = $candidate->rPlayer;
									$topic->rColor = $currentPlayer->getRColor();
									$topic->dCreation = Utils::now();
									$topic->dLastMessage = Utils::now();

									$forumTopicManager->add($topic);

									if ($currentPlayer->getRColor() == ColorResource::CARDAN) {
										$vote = new \App\Modules\Demeter\Model\Election\Vote();

										$vote->rPlayer = $currentPlayer->getId();
										$vote->rCandidate = $currentPlayer->getId();
										$vote->rElection = $election->id;
										$vote->dVotation = Utils::now();

										$voteManager->add($vote);
									}
									$this->addFlash('success', 'Candidature déposée.');

									return $this->redirectToRoute('view_faction_election', ['candidate' => $candidate->id]);
								} else {
									$entityManager->remove($candidate);

									$this->addFlash('success', 'Candidature retirée.');

									return $this->redirect($request->headers->get('referer'));
								}
							} else {
								throw new ErrorException('Informations manquantes sur les choix.');
							}
						} else {
							throw new ErrorException('Vous ne pouvez présenter ou retirer votre candidature qu\'en période de campagne.');
						}
					} else {
						throw new ErrorException('Vous ne pouvez pas vous présenter, vous ne faite pas partie de l\'élite.');
					}
				} else {
					throw new ErrorException('Cette election ne se déroule pas dans la faction du joueur.');
				}
			} else {
				throw new ErrorException('Cette election n\'existe pas.');
			}
		} else {
			throw new ErrorException('Informations manquantes.');
		}
	}
}
