<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\Utils;
use App\Modules\Demeter\Domain\Repository\Election\CandidateRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\ElectionRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Manager\Election\CandidateManager;
use App\Modules\Demeter\Manager\Election\ElectionManager;
use App\Modules\Demeter\Manager\Election\VoteManager;
use App\Modules\Demeter\Manager\Forum\ForumMessageManager;
use App\Modules\Demeter\Manager\Forum\ForumTopicManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\Validator\IsFromFaction;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class ViewElection extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ElectionRepositoryInterface $electionRepository,
		CandidateRepositoryInterface $candidateRepository,
		VoteRepositoryInterface $voteRepository,
		PlayerRepositoryInterface $playerRepository,
		ForumMessageManager $forumMessageManager,
	): Response {
		$faction = $currentPlayer->faction;

		$election = $electionRepository->getFactionLastElection($faction);

		$data = [
			'faction' => $faction,
			'election' => $election,
		];

		if (null !== $election) {
			$candidates = $candidateRepository->getByElection($election);

			$data['candidates'] = $candidates;
			$data['is_candidate'] = 1 <= count(array_filter(
				$candidates,
				fn (Candidate $candidate) => $candidate->player->id === $currentPlayer->id
			));

			if ($faction->isInElection()) {
				$votes = $voteRepository->getElectionVotes($election);

				$data['player_vote'] = $voteRepository->getPlayerVote($currentPlayer, $election);
				$data['votes'] = $votes;
				$data['faction_members'] = $playerRepository->getBySpecification(new IsFromFaction($faction));

				$candidate = ($request->query->has('candidate') && ($candidate = $candidateRepository->get(Uuid::fromString($request->query->get('candidate')))) !== null)
					? $candidate
					: ([] !== $candidates ? $candidates[0] : null);

				$data['candidate'] = $candidate;

				if (null !== $candidate) {
					if ($faction->isRoyalistic()) {
						$data['putsch_supporters_count'] = count(array_filter($votes, fn (Vote $vote) => $vote->candidate->player->id === $candidate->player->id));
						$endPutsch = Utils::addSecondsToDate($faction->lastElectionHeldAt, Color::PUTSCHTIME);
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
