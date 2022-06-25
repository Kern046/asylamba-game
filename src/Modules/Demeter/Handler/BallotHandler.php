<?php

namespace App\Modules\Demeter\Handler;

use App\Classes\Library\DateTimeConverter;
use App\Classes\Library\Format;
use App\Classes\Library\Utils;
use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\ElectionRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Message\BallotMessage;
use App\Modules\Demeter\Message\CampaignMessage;
use App\Modules\Demeter\Message\SenateUpdateMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class BallotHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private PlayerRepositoryInterface $playerRepository,
		private MessageBusInterface $messageBus,
		private NotificationManager $notificationManager,
		private NotificationRepositoryInterface $notificationRepository,
		private ElectionRepositoryInterface $electionRepository,
		private VoteRepositoryInterface $voteRepository,
		private ConversationRepositoryInterface $conversationRepository,
		private NextElectionDateCalculator $nextElectionDateCalculator,
	) {
	}

	public function __invoke(BallotMessage $message): void
	{
		$faction = $this->colorRepository->get($message->getFactionId());
		if (null === ($election = $this->electionRepository->getFactionLastElection($faction))) {
			return;
		}

		$chiefId = (($leader = $this->playerRepository->getFactionLeader($faction)) !== null) ? $leader->id : false;

		$votes = $this->voteRepository->getElectionVotes($election);

		/** @var array{candidate: Candidate, votesCount: int } $ballot */
		$ballot = [];
		$listCandidate = [];

		foreach ($votes as $vote) {
			$candidateId = $vote->candidate->id->toBase32();
			if (!array_key_exists($candidateId, $ballot)) {
				$ballot[$candidateId] = [$vote->candidate, 0];
			}
			++$ballot[$candidateId][1];
		}

		if (!empty($ballot)) {
			// @TODO optimize SQL queries
			foreach ($ballot as $candidateId => [$candidate, $votesCount]) {
				$listCandidate[] = [
					'id' => $candidateId,
					'name' => $candidate->player->name,
					'vote' => $vote,
				];
			}

			uasort($listCandidate, function ($a, $b) {
				if ($a['vote'] == $b['vote']) {
					return 0;
				}

				return $a['vote'] > $b['vote']
					? -1 : 1;
			});
		}
		reset($listCandidate);

		$convPlayer = $this->playerRepository->getFactionAccount($faction);

		$conv = $this->conversationRepository->getOneByPlayer($convPlayer);

		if ($faction->isDemocratic()) {
			if (count($ballot) > 0) {
				arsort($ballot);
				reset($ballot);

				$governmentMembers = $this->playerRepository->getGovernmentMembers($faction);
				$newChief = $this->playerRepository->get(key($ballot));

				$this->mandate($faction, $governmentMembers, $newChief, $chiefId, true, $conv, $convPlayer, $listCandidate);
			} else {
				$this->mandate($faction, 0, 0, $chiefId, false, $conv, $convPlayer, $listCandidate);
			}
		} elseif ($faction->isRoyalistic()) {
			if (count($ballot) > 0) {
				arsort($ballot);
				reset($ballot);

				if (key($ballot) == $chiefId) {
					next($ballot);
				}

				if (((current($ballot) / ($faction->activePlayers + 1)) * 100) >= Color::PUTSCHPERCENTAGE) {
					$governmentMembers = $this->playerRepository->getGovernmentMembers($faction);
					$newChief = $this->playerRepository->get(key($ballot));
					$this->mandate($faction, $governmentMembers, $newChief, $chiefId, true, $conv, $convPlayerID, $listCandidate);
				} else {
					$looser = $this->playerRepository->get(key($ballot));
					$this->mandate($faction, 0, $looser, $chiefId, false, $conv, $convPlayerID, $listCandidate);
				}
			}
		} else {
			if (($leader = $this->playerRepository->getFactionLeader($faction)) !== null) {
				if (($candidate = $this->candidateManager->getByElectionAndPlayer($election, $leader)) !== null) {
					if (0 == rand(0, 1)) {
						$ballot = [];
					}
				}
			}
			if (count($ballot) > 0) {
				reset($ballot);
				$aleaNbr = rand(0, count($ballot) - 1);

				for ($i = 0; $i < $aleaNbr; ++$i) {
					next($ballot);
				}

				$governmentMembers = $this->playerRepository->getGovernmentMembers($faction);
				$newChief = $this->playerRepository->get(key($ballot));

				$this->mandate($faction, $governmentMembers, $newChief, $chiefId, true, $conv, $convPlayerID, $listCandidate);
			} else {
				$this->mandate($faction, 0, 0, $chiefId, false, $conv, $convPlayerID, $listCandidate);
			}
		}
	}

	public function mandate(
		Color $color,
		$governmentMembers,
		$newChief,
		$idOldChief,
		$hadVoted,
		Conversation $conv,
		Player $convPlayer,
		array $candidates,
	): void {
		// préparation de la conversation
		$conv->lastMessageAt = new \DateTimeImmutable();

		// désarchiver tous les users
		$users = $conv->players;
		foreach ($users as $user) {
			$user->convStatement = ConversationUser::CS_DISPLAY;
		}
		if ($hadVoted) {
			/*			$date = new DateTime($this->dLastElection);
						$date->modify('+' . $this->mandateDuration + self::ELECTIONTIME + self::CAMPAIGNTIME . ' second');
						$date = $date->format('Y-m-d H:i:s');
						$this->dLastElection = $date;*/

			foreach ($governmentMembers as $governmentMember) {
				$governmentMember->status = Player::PARLIAMENT;
			}

			$newChief->status = Player::CHIEF;

			$color->lastElectionHeldAt = new \DateTimeImmutable();
			$color->electionStatement = Color::MANDATE;

			$statusArray = $color->status;
			if ($color->isDemocratic()) {
				$this->messageBus->dispatch(
					new CampaignMessage($color->id),
					[DateTimeConverter::to_delay_stamp($this->nextElectionDateCalculator->getCampaignStartDate($color))],
				);
				$notif = NotificationBuilder::new()
					->setTitle('Votre avez été élu')
					->setContent(NotificationBuilder::paragraph(sprintf(
						'Le peuple vous a soutenu, vous avez été élu %s de votre faction.',
						$statusArray[Player::CHIEF - 1],
					)))
					->for($newChief);
				$this->notificationRepository->save($notif);

				$message = new ConversationMessage(
					id: Uuid::v4(),
					conversation: $conv,
					player: $convPlayer,
					content: sprintf(
						'La période électorale est terminée.
						Un nouveau dirigeant a été élu pour faire valoir la force de %s à travers la galaxie.
						Longue vie à <strong>%s</strong>.<br /><br />Voici les résultats des élections :<br /><br />
						%s',
						ColorResource::getInfo($color->identifier, 'popularName'),
						current($candidates)['name'],
						array_map(
							fn ($player) => sprintf(
								'%s a reçu %d vote%s<br />',
								$player['name'],
								$player['vote'],
								Format::plural($player['vote']),
							),
							$candidates,
						)
					),
					createdAt: new \DateTimeImmutable(),
				);
				$this->conversationRepository->save($message);
			} elseif ($color->isRoyalistic()) {
				$this->messageBus->dispatch(
					new SenateUpdateMessage($color->getId()),
					[DateTimeConverter::to_delay_stamp(date('Y-m-d H:i:s', time() + $color->mandateDuration))],
				);
				$notif = new Notification();
				$notif->dSending = Utils::now();
				$notif->setRPlayer($newChief->id);
				$notif->setTitle('Votre coup d\'état a réussi');
				$notif->addBeg()
					->addTxt(' Le peuple vous a soutenu, vous avez renversé le '.$statusArray[Player::CHIEF - 1].' de votre faction et avez pris sa place.');
				$this->notificationManager->add($notif);

				if ($idOldChief) {
					$notif = new Notification();
					$notif->dSending = Utils::now();
					$notif->setRPlayer($idOldChief);
					$notif->setTitle('Un coup d\'état a réussi');
					$notif->addBeg()
						->addTxt(' Le joueur ')
						->addLnk('embassy/player-'.$newChief->id, $newChief->name)
						->addTxt(' a fait un coup d\'état, vous êtes évincé du pouvoir.');
					$this->notificationManager->add($notif);
				}

				// création du message
				reset($candidates);
				if (current($candidates)['id'] == $idOldChief) {
					next($candidates);
				}
				$message = new ConversationMessage();
				$message->rConversation = $conv->id;
				$message->rPlayer = $convPlayer;
				$message->type = ConversationMessage::TY_STD;
				$message->dCreation = Utils::now();
				$message->dLastModification = null;
				$message->content = 'Un putsch a réussi, un nouveau dirigeant va faire valoir la force de '.$color->popularName.' à travers la galaxie. Longue vie à <strong>'.current($candidates)['name'].'</strong>.<br /><br />De nombreux membres de la faction ont soutenu le mouvement révolutionnaire :<br /><br />';
				$message->content .= current($candidates)['name'].' a reçu le soutien de '.Format::number((current($candidates)['vote'] / ($color->activePlayers + 1)) * 100).'% de la population.<br />';
				$this->conversationMessageManager->add($message);
			} else {
				$date = new \DateTime($color->dLastElection);
				$date->modify('+'.$color->mandateDuration.' second');
				$this->messageBus->dispatch(
					new CampaignMessage($color->id),
					[DateTimeConverter::to_delay_stamp($date->format('Y-m-d H:i:s'))],
				);

				$notif = new Notification();
				$notif->dSending = Utils::now();
				$notif->setRPlayer($newChief->id);
				$notif->setTitle('Vous avez été nommé Guide');
				$notif->addBeg()
					->addTxt(' Les Oracles ont parlé, vous êtes désigné par la Grande Lumière pour guider Cardan vers la Gloire.');
				$this->notificationManager->add($notif);

				$message = new ConversationMessage();
				$message->rConversation = $conv->id;
				$message->rPlayer = $convPlayer;
				$message->type = ConversationMessage::TY_STD;
				$message->dCreation = Utils::now();
				$message->dLastModification = null;
				$message->content = 'Les Oracles ont parlé, un nouveau dirigeant va faire valoir la force de '.$color->popularName.' à travers la galaxie. Longue vie à <strong>'.current($candidates)['name'].'</strong>.<br /><br /><br /><br />';
				$this->conversationMessageManager->add($message);
			}
		} else {
			$noChief = false;
			if (($oldChief = $this->playerRepository->get($idOldChief)) === null) {
				$noChief = true;
				$oldChief = $this->playerRepository->getByName($color->officialName);
			}
			/*			$date = new DateTime($this->dLastElection);
						$date->modify('+' . $this->mandateDuration + self::ELECTIONTIME + self::CAMPAIGNTIME . ' second');
						$date = $date->format('Y-m-d H:i:s');
						$this->dLastElection = $date;*/
			$color->dLastElection = Utils::now();
			$color->electionStatement = Color::MANDATE;

			switch ($color->regime) {
				case Color::REGIME_DEMOCRATIC:
					$date = new \DateTime($color->dLastElection);
					$date->modify('+'.$color->mandateDuration.' second');
					$this->messageBus->dispatch(
						new CampaignMessage($color->getId()),
						[DateTimeConverter::to_delay_stamp($date->format('Y-m-d H:i:s'))],
					);

					if ($idOldChief) {
						$notif = new Notification();
						$notif->dSending = Utils::now();
						$notif->setRPlayer($idOldChief);
						$notif->setTitle('Vous demeurez '.ColorResource::getInfo($color->getId(), 'status')[Player::CHIEF - 1]);
						$notif->addBeg()
							->addTxt(' Aucun candidat ne s\'est présenté oour vous remplacer lors des dernières élections. Par conséquent, vous êtes toujours à la tête de '.$color->popularName);
						$this->notificationManager->add($notif);
					}
					// création du message
					$message = new ConversationMessage();
					$message->rConversation = $conv->id;
					$message->rPlayer = $convPlayer;
					$message->type = ConversationMessage::TY_STD;
					$message->dCreation = Utils::now();
					$message->dLastModification = null;
					$message->content = ' La période électorale est terminée. Aucun candidat ne s\'est présenté pour prendre la tête de '.$color->popularName.'.';
					$message->content .=
						(false === $noChief)
							? '<br>Par conséquent, '.$oldChief->getName().' est toujours au pouvoir.'
							: '<br>Par conséquent, le siège du pouvoir demeure vacant.'
					;
					$this->conversationMessageManager->add($message);
					break;
				case Color::REGIME_ROYALISTIC:
					$notif = new Notification();
					$notif->dSending = Utils::now();
					$notif->setRPlayer($newChief->id);
					$notif->setTitle('Votre coup d\'état a échoué');
					$notif->addBeg()
						->addTxt(' Le peuple ne vous a pas soutenu, l\'ancien gouvernement reste en place.');
					$this->notificationManager->add($notif);

					if ($idOldChief) {
						$notif = new Notification();
						$notif->dSending = Utils::now();
						$notif->setRPlayer($idOldChief);
						$notif->setTitle('Un coup d\'état a échoué');
						$notif->addBeg()
							->addTxt(' Le joueur ')
							->addLnk('embassy/player-'.$newChief->id, $newChief->name)
							->addTxt(' a tenté un coup d\'état, celui-ci a échoué.');
						$this->notificationManager->add($notif);
					}
					$message = new ConversationMessage();
					$message->rConversation = $conv->id;
					$message->rPlayer = $convPlayer;
					$message->type = ConversationMessage::TY_STD;
					$message->dCreation = Utils::now();
					$message->dLastModification = null;
					$message->content = 'Un coup d\'état a échoué. '.$oldChief->getName().' demeure le dirigeant de '.$color->popularName.'.';
					$this->conversationMessageManager->add($message);
					break;
				case Color::REGIME_THEOCRATIC:
					$date = new \DateTime($color->dLastElection);
					$date->modify('+'.$color->mandateDuration.' second');
					$this->messageBus->dispatch(
						new CampaignMessage($color->getId()),
						[DateTimeConverter::to_delay_stamp($date->format('Y-m-d H:i:s'))],
					);

					if ($idOldChief) {
						$notif = new Notification();
						$notif->dSending = Utils::now();
						$notif->setRPlayer($idOldChief);
						$notif->setTitle('Vous avez été nommé Guide');
						$notif->addBeg()
							->addTxt(' Les Oracles ont parlé, vous êtes toujours désigné par la Grande Lumière pour guider Cardan vers la Gloire.');
						$this->notificationManager->add($notif);
					}
					$message = new ConversationMessage();
					$message->rConversation = $conv->id;
					$message->rPlayer = $convPlayer;
					$message->type = ConversationMessage::TY_STD;
					$message->dCreation = Utils::now();
					$message->dLastModification = null;
					$message->content = 'Nul ne s\'est soumis au regard des dieux pour conduire '.$color->popularName.' vers sa gloire.';
					$message->content .=
						(false === $noChief)
							? $oldChief->getName().' demeure l\'élu des dieux pour accomplir leurs desseins dans la galaxie.'
							: 'Par conséquent, le siège du pouvoir demeure vacant.'
					;
					$this->conversationMessageManager->add($message);
					break;
			}
		}
		$this->entityManager->flush();
	}
}
