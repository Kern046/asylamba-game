<?php

/**
 * Vote Manager.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 06.10.13
 */

namespace App\Modules\Demeter\Manager\Election;

use App\Classes\Entity\EntityManager;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Zeus\Model\Player;

class VoteManager
{
	public function __construct(protected EntityManager $entityManager)
	{
	}

	/**
	 * @return array
	 */
	public function getElectionVotes(Election $election)
	{
		return $this->entityManager->getRepository(Vote::class)->getElectionVotes($election->id);
	}

	/**
	 * @return Vote
	 */
	public function getPlayerVote(Player $player, Election $election)
	{
		return $this->entityManager->getRepository(Vote::class)->getPlayerVote($player->id, $election->id);
	}

	/**
	 * @return int
	 */
	public function add(Vote $vote)
	{
		$this->entityManager->persist($vote);
		$this->entityManager->flush($vote);

		return $vote->id;
	}
}
