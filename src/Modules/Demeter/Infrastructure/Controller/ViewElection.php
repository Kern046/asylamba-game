<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\Utils;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Manager\Election\CandidateManager;
use App\Modules\Demeter\Manager\Election\ElectionManager;
use App\Modules\Demeter\Manager\Election\VoteManager;
use App\Modules\Demeter\Manager\Forum\ForumMessageManager;
use App\Modules\Demeter\Manager\Forum\ForumTopicManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewElection extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CandidateManager $candidateManager,
		ColorManager $colorManager,
		ElectionManager $electionManager,
		VoteManager $voteManager,
		PlayerManager $playerManager,
		ForumTopicManager $forumTopicManager,
		ForumMessageManager $forumMessageManager,
	): Response {
		$faction = $colorManager->get($currentPlayer->getRColor());

		$election = $electionManager->getFactionLastElection($faction->id);

		$data = [
			'faction' => $faction,
			'election' => $election,
		];

		if (null !== $election) {
			$candidates = $candidateManager->getByElection($election);

			$data['candidates'] = $candidates;
			$data['is_candidate'] = 1 <= count(array_filter(
				$candidates,
				fn (Candidate $candidate) => $candidate->rPlayer === $currentPlayer->getId()
			));

			if ($faction->isInElection()) {
				$votes = $voteManager->getElectionVotes($election);

				$data['player_vote'] = $voteManager->getPlayerVote($currentPlayer, $election);
				$data['votes'] = $votes;
				$data['faction_members'] = $playerManager->getFactionPlayers($faction->getId());

				$candidate = ($request->query->has('candidate') && ($candidate = $candidateManager->get($request->query->get('candidate'))) !== null) ? $candidate : ([] !== $candidates ? $candidates[0] : null);

				$data['candidate'] = $candidate;

				if (null !== $candidate) {
					if ($faction->isRoyalistic()) {
						$data['putsch_supporters_count'] = count(array_filter($votes, fn (Vote $vote) => $vote->rCandidate === $candidate->rPlayer));
						$endPutsch = Utils::addSecondsToDate($faction->dLastElection, Color::PUTSCHTIME);
						$data['remaining_putsch_time'] = Utils::interval(Utils::now(), $endPutsch, 's');
					}

					$forumTopicManager->load(
						[
							'rForum' => 30,
							'rPlayer' => $candidate->rPlayer,
						],
						['id', 'DESC'],
						[0, 1],
						$currentPlayer->getId(),
					);

					if (1 == $forumTopicManager->size()) {
						$topic = $forumTopicManager->get(0);
						$forumTopicManager->updateLastView($topic, $currentPlayer->getId());

						$forumMessageManager->load(['rTopic' => $topic->id], ['dCreation', 'DESC', 'id', 'DESC']);

						$data['topic'] = $topic;
						$data['topic_messages'] = $forumMessageManager->getAll();
					}
				}
			}
		}

		return $this->render('pages/demeter/faction/election.html.twig', $data);
	}
}
