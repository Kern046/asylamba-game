<?php

namespace App\Modules\Zeus\Infrastructure\Controller;

use App\Classes\Container\ArrayList;
use App\Classes\Library\Security;
use App\Classes\Worker\API;
use App\Modules\Ares\Model\Ship;
use App\Modules\Athena\Application\Handler\OrbitalBasePointsHandler;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Event\PlaceOwnerChangeEvent;
use App\Modules\Gaia\Galaxy\GalaxyConfiguration;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Promethee\Domain\Repository\ResearchRepositoryInterface;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Helper\ResearchHelper;
use App\Modules\Promethee\Model\Research;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Helper\CheckName;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class CreateCharacter extends AbstractController
{
	public function __construct(
		private readonly API $api,
		private readonly ColorRepositoryInterface $colorRepository,
		private readonly PlayerRepositoryInterface $playerRepository,
		private readonly PlayerManager $playerManager,
		private readonly GalaxyConfiguration $galaxyConfiguration,
		private readonly NotificationManager $notificationManager,
		private readonly TechnologyRepositoryInterface $technologyRepository,
		private readonly ResearchHelper $researchHelper,
		private readonly EventDispatcherInterface $eventDispatcher,
		private readonly PlaceManager $placeManager,
		private readonly OrbitalBasePointsHandler $orbitalBasePointsHandler,
		private readonly OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private readonly SectorRepositoryInterface $sectorRepository,
		private readonly PlaceRepositoryInterface $placeRepository,
		private readonly ResearchRepositoryInterface $researchRepository,
		private readonly EntityManagerInterface $entityManager,
		private readonly Security $security,
	) {
	}

	#[Route(
		path: '/create-character/{step}/{highMode}',
		name: 'create_character',
		requirements: [
			'step' => 'faction-choice|profile|place-choice|save',
		],
		defaults: [
			'highMode' => false,
			'step' => 'faction-choice',
		],
		methods: ['GET', 'POST'],
	)]
	public function __invoke(
		Request $request,
		string $step,
		bool $highMode
	): Response {
		$globalParameters = [
			'google_plus_link' => $this->getParameter('google_plus_link'),
			'twitter_link' => $this->getParameter('twitter_link'),
			'facebook_link' => $this->getParameter('facebook_link'),
		];

		return match ($step) {
			'faction-choice' => $this->renderFactionChoiceStep($request, $highMode, $globalParameters),
			'profile' => $this->renderProfileStep($request, $globalParameters),
			'place-choice' => $this->renderPlaceChoiceStep($request, $globalParameters),
			'save' => $this->save($request),
		};
	}

	private function renderFactionChoiceStep(
		Request $request,
		bool $highMode,
		array $globalParameters,
	): Response {
		$session = $request->getSession();
		if ($request->query->has('bindKey')) {
			// extraction du bindkey
			$query = $this->security->uncrypt($request->query->get('bindKey'));
			$bindkey = $this->security->extractBindKey($query);
			$time = $this->security->extractTime($query);

			// vérification de la validité du bindkey
			if (abs((int) $time - time()) <= 300) {
				$session->set('prebindkey', $bindkey);

				// mode de création de joueur
				$session->set('high-mode', $this->getParameter('highmode') && $highMode);

				return $this->redirectToRoute('create_character', ['highMode' => $highMode]);
			} else {
				throw new UnauthorizedHttpException('Invalid bindkey');
			}
		} elseif ($session->has('prebindkey')) {
			if ('enabled' === $this->getParameter('apimode')) {
				// utilisation de l'API

				if ($this->api->userExist($session->get('prebindkey'))) {
					if (null === $this->playerRepository->findOneBy(['bind' => $session->get('prebindkey')])) {
						$session->set('inscription', new ArrayList());
						$session->get('inscription')->add('bindkey', $session->get('prebindkey'));
						$session->get('inscription')->add('portalPseudo', $this->api->data['userInfo']['pseudo']);

						// check du rgodfather
						if (!empty($this->api->data['userInfo']['sponsorship'])) {
							list($server, $player) = explode('#', $this->api->data['userInfo']['sponsorship']);

							if ($server == $this->getParameter('server_id')) {
								$session->set('rgodfather', $player);
							}
						}
					} else {
						return $this->redirect($this->getParameter('getout_root').'serveurs/message-useralreadysigned');
					}
				} else {
					return $this->redirect($this->getParameter('getout_root').'serveurs/message-unknowuser');
				}
			} else {
				$session->set('inscription', new ArrayList());
				$session->get('inscription')->add('bindkey', $session->get('prebindkey'));
				$session->get('inscription')->add('portalPseudo', null);
			}
		} else {
			return $this->redirect($this->getParameter('getout_root').'serveurs/message-nobindkey');
		}

		return $this->render('pages/zeus/registration/faction_choice.html.twig', array_merge([
			'sorted_factions' => $this->colorRepository->getAllByActivePlayersNumber(),
		], $globalParameters));
	}

	private function renderProfileStep(Request $request, array $globalParameters): Response
	{
		$session = $request->getSession();
		if (!$session->has('inscription')) {
			return $this->redirect($this->getParameter('getout_root').'serveurs/message-forbiddenaccess');
		}
		// création du tableau des alliances actives
		// entre 1 et 7
		// alliance pas défaites
		// algorythme de fermeture automatique des alliances (auto-balancing)
		$openFactions = $this->colorRepository->getOpenFactions();

		$ally = array_map(fn (Color $faction) => $faction->identifier, $openFactions);

		if ($request->query->has('factionIdentifier') && in_array($request->query->get('factionIdentifier'), $ally)) {
			$session->get('inscription')->add('ally', $request->query->get('factionIdentifier'));
		} elseif (!$session->get('inscription')->exist('ally')) {
			throw new BadRequestHttpException('faction inconnues ou non-sélectionnable');
		}

		$nbAvatars = $this->getParameter('nb_avatar');

		return $this->render('pages/zeus/registration/profile.html.twig', array_merge([
			'avatars' => $this->getAvatars($session->get('inscription')->get('ally'), $nbAvatars),
			'nb_avatars' => $nbAvatars,
		], $globalParameters));
	}

	private function getAvatars(int $factionIdentifier, int $nbAvatars): array
	{
		$avatars = [];
		for ($i = 1; $i <= $nbAvatars; ++$i) {
			if (!\in_array($i, [77, 19])) {
				// @TODO simplify with str_pad function
				$avatar = $i < 10 ? '00' : '0';
				$avatar .= $i.'-'.$factionIdentifier;
				$avatars[] = $avatar;
			}
		}
		\shuffle($avatars);

		return $avatars;
	}

	private function renderPlaceChoiceStep(Request $request, array $globalParameters): Response
	{
		$session = $request->getSession();
		if ($session->has('inscription')) {
			if (null === $this->playerRepository->findOneBy(['name' => $request->request->get('pseudo')])) {
				$check = new CheckName();

				if ($request->request->has('pseudo') && $check->checkLength($request->request->get('pseudo')) && $check->checkChar($request->request->get('pseudo'))) {
					$session->get('inscription')->add('pseudo', $request->request->get('pseudo'));

					// check avatar
					if ((int) $request->request->get('avatar') > 0 && (int) $request->request->get('avatar') <= $this->getParameter('nb_avatar')) {
						$session->get('inscription')->add('avatar', $request->request->get('avatar'));
					} elseif (!$session->get('inscription')->exist('avatar')) {
						throw new BadRequestHttpException('Cet avatar n\'existe pas ou est invalide');
					}
				} elseif (!$session->get('inscription')->exist('pseudo')) {
					throw new BadRequestHttpException('Votre pseudo est trop long, trop court ou contient des caractères non-autorisés');
				}
			} elseif (!$session->get('inscription')->exist('pseudo')) {
				throw new BadRequestHttpException('Ce pseudo est déjà utilisé par un autre joueur');
			}
		} else {
			return $this->redirect('/serveurs/message-forbiddenaccess');
		}

		return $this->render('pages/zeus/registration/place_choice.html.twig', array_merge([
			'galaxy_configuration' => $this->galaxyConfiguration,
			'sectors' => $this->sectorRepository->getAll(),
		], $globalParameters));
	}

	private function save(Request $request): Response
	{
		$session = $request->getSession();
		if (null === $session->get('bindkey') || null === $this->playerRepository->getByBindKey($session->get('bindkey'))) {
			if ($session->has('inscription')) {
				$check = new CheckName();

				if ($request->request->has('base') && $check->checkLength($request->request->get('base'))) {
					if ($check->checkChar($request->request->get('base'))) {
						$session->get('inscription')->add('base', $request->request->get('base'));

						$sectors = $this->sectorRepository->getAll();

						$factionSectors = [];
						foreach ($sectors as $sector) {
							if ($sector->faction?->identifier == $session->get('inscription')->get('ally')) {
								$factionSectors[] = $sector->id;
							}
						}
						if (in_array($request->request->get('sector'), $factionSectors)) {
							$session->get('inscription')->add('sector', $request->request->get('sector'));
						} else {
							throw new BadRequestHttpException('il faut sélectionner un des secteurs de la couleur de votre faction');
						}
					} else {
						throw new BadRequestHttpException('le nom de votre base ne doit pas contenir de caractères spéciaux');
					}
				} else {
					throw new BadRequestHttpException('le nom de votre base doit contenir entre '.$check->getMinLength().' et '.$check->getMaxLength().' caractères');
				}
			} else {
				return $this->redirect($this->getParameter('getout_root').'serveurs/message-forbiddenaccess');
			}
		} else {
			return $this->redirect($this->getParameter('getout_root').'serveurs/message-forbiddenaccess');
		}

		return $this->persistPlayer($request);
	}

	private function persistPlayer(Request $request): Response
	{
		$session = $request->getSession();
		try {
			$this->entityManager->beginTransaction();

			$faction = $this->colorRepository->getOneByIdentifier($session->get('inscription')->get('ally'))
				?? throw new BadRequestHttpException('Invalid faction identifier');
			// AJOUT DU JOUEUR EN BASE DE DONNEE
			$player = new Player();

			// ajout des variables inchangées
			$player->bind = $session->get('inscription')->get('bindkey');
			$player->faction = $faction;
			$player->name = trim($session->get('inscription')->get('pseudo'));
			$player->avatar = $session->get('inscription')->get('avatar');

			$godFather = null;

			if ($session->has('rgodfather')) {
				$godFather = $this->playerRepository->get($session->get('rgodfather'))
					?? throw new BadRequestHttpException('Godfather not found');

				$player->godfather = $godFather;
			}

			$player->status = Player::STANDARD;
			$player->uPlayer = new \DateTimeImmutable();

			$player->victory = 0;
			$player->defeat = 0;

			$player->stepTutorial = 1;
			$player->stepDone = true;

			$player->iUniversity = 1000;
			$player->partNaturalSciences = 25;
			$player->partLifeSciences = 25;
			$player->partSocialPoliticalSciences = 25;
			$player->partInformaticEngineering = 25;
			// @TODO adapt this vlaue depending on the chosen avatar or player's choice
			$player->sex = 1;

			$player->dInscription = new \DateTimeImmutable();
			$player->dLastConnection = new \DateTimeImmutable();
			$player->dLastActivity = new \DateTimeImmutable();

			$player->premium = 0;
			$player->statement = Player::ACTIVE;

			// ajout des variables dépendantes
			if ($session->get('high-mode')) {
				$player->credit = 10000000;
				$player->experience = 18000;
				$player->level = 5;
			} else {
				$player->credit = 5000;
				$player->experience = 630;
				$player->level = 1;
			}

			$this->playerRepository->save($player);

			if (null !== $godFather) {
				// send a message to the godfather
				$n = new Notification(
					id: Uuid::v4(),
					player: $player->godfather,
					title: 'Votre filleul s\'est inscrit',
				);
				$n->addBeg()->addTxt('Un de vos amis a créé un compte.')->addSep();
				$n->addTxt('Vous pouvez le contacter, son nom de joueur est ');
				$n->addLnk('embassy/player-'.$player->getId(), '"'.$player->name.'"')->addTxt('.');
				$n->addBrk()->addTxt('Vous venez de gagner 1000 crédits. Vous en gagnerez 1 million de plus lorsqu\'il atteindra le niveau 3.');
				$n->addEnd();

				$this->notificationManager->add($n);

				// add 1000 credits to the godfather
				$this->playerManager->increaseCredit($godFather, 1000);

				// remove godFather from session
				$session->remove('rgodfather');
			}

			// INITIALISATION DES RECHERCHES
			// rendre aléatoire
			$rs = new Research(
				Uuid::v4(),
				player: $player,
				naturalToPay: $this->researchHelper->getInfo(Research::MATH, 'level', 1, 'price'),
				lifeToPay: $this->researchHelper->getInfo(Research::LAW, 'level', 1, 'price'),
				socialToPay: $this->researchHelper->getInfo(Research::ECONO, 'level', 1, 'price'),
				informaticToPay: $this->researchHelper->getInfo(Research::NETWORK, 'level', 1, 'price'),
				naturalTech: Research::MATH,
				lifeTech: Research::LAW,
				socialTech: Research::ECONO,
				informaticTech: Research::NETWORK,
			);

			if ($session->get('high-mode')) {
				$rs->mathLevel = 15;
				$rs->physLevel = 15;
				$rs->chemLevel = 15;
				$rs->bioLevel = 15;
				$rs->mediLevel = 15;
				$rs->econoLevel = 15;
				$rs->psychoLevel = 15;
				$rs->networkLevel = 15;
				$rs->algoLevel = 15;
				$rs->statLevel = 15;
			}

			$this->researchRepository->save($rs);

			// choix de la place
			$sector = $this->sectorRepository->get(Uuid::fromString($session->get('inscription')->get('sector')))
				?? throw new BadRequestHttpException('Sector not found');
			$candidatePlaces = $this->placeRepository->findPlacesIdsForANewBase($sector);

			$placeId = $candidatePlaces[rand(0, count($candidatePlaces) - 1)];
			$place = $this->placeRepository->get($placeId);
			// CREATION DE LA BASE ORBITALE
			$ob = new OrbitalBase(
				id: Uuid::v4(),
				place: $place,
				player: $player,
				name: $session->get('inscription')->get('base'),
			);

			// création des premiers bâtiments
			if ($session->get('high-mode')) {
				// batiments haut-level
				$ob->levelGenerator = 35;
				$ob->levelRefinery = 35;
				$ob->levelDock1 = 35;
				$ob->levelDock2 = 10;
				$ob->levelDock3 = 0;
				$ob->levelTechnosphere = 35;
				$ob->levelCommercialPlateforme = 10;
				$ob->levelStorage = 35;
				$ob->levelRecycling = 15;
				$ob->levelSpatioport = 10;
				$ob->resourcesStorage = 3000000;

				// remplir le dock
				$ob->addShips(Ship::TYPE_PEGASE, 50);
				$ob->addShips(Ship::TYPE_SATYRE, 50);
				$ob->addShips(Ship::TYPE_CHIMERE, 10);
				$ob->addShips(Ship::TYPE_SIRENE, 10);
				$ob->addShips(Ship::TYPE_DRYADE, 5);
				$ob->addShips(Ship::TYPE_MEDUSE, 5);
				$ob->addShips(Ship::TYPE_GRIFFON, 2);
				$ob->addShips(Ship::TYPE_CYCLOPE, 2);
				$ob->addShips(Ship::TYPE_MINOTAURE, 1);
				$ob->addShips(Ship::TYPE_HYDRE, 1);
				$ob->addShips(Ship::TYPE_CERBERE, 0);
				$ob->addShips(Ship::TYPE_PHENIX, 0);
			} else {
				$ob->levelGenerator = 1;
				$ob->levelRefinery = 1;
				$ob->levelDock1 = 0;
				$ob->levelDock2 = 0;
				$ob->levelDock3 = 0;
				$ob->levelTechnosphere = 0;
				$ob->levelCommercialPlateforme = 0;
				$ob->levelStorage = 1;
				$ob->levelRecycling = 0;
				$ob->levelSpatioport = 0;
				$ob->resourcesStorage = 1000;
			}

			$this->orbitalBasePointsHandler->updatePoints($ob);

			// initialisation des investissement
			$ob->iSchool = 500;
			$ob->iAntiSpy = 500;

			// ajout de la base
			$ob->updatedAt = new \DateTimeImmutable();
			$ob->createdAt = new \DateTimeImmutable();

			$this->createPlayerTechnology($player, $session->get('high-mode', false));

			$this->orbitalBaseRepository->save($ob);

			$this->placeManager->turnAsSpawnPlace($place, $player);

			$this->entityManager->commit();

			$this->eventDispatcher->dispatch(new PlaceOwnerChangeEvent($place), PlaceOwnerChangeEvent::NAME);

			// modification de la place

			// confirmation au portail
			if ('enabled' === $this->getParameter('apimode')) {
				$return = $this->api->confirmInscription($session->get('inscription')->get('bindkey'));
			}
			// clear les sessions
			$session->remove('inscription');
			$session->remove('prebindkey');

			// ajout aux conversation de faction et techniques
			$readingDate = new \DateTimeImmutable();

//			if (($factionAccount = $this->playerRepository->getFactionAccount($player->faction)) !== null) {
//				$this->conversationManager->load(
//					[
//					'cu.rPlayer' => [$this->getParameter('id_jeanmi'), $factionAccount->id],
//				],
//					[],
//					[0, 2]
//				);
//
//				for ($i = 0; $i < $this->conversationManager->size(); ++$i) {
//					$user = new ConversationUser(
//						id: Uuid::v4(),
//						conversation: '',
//						player: $player,
//						lastViewedAt: $readingDate,
//						playerStatus: ConversationUser::US_STANDARD,
//						conversationStatus: ConversationUser::CS_ARCHIVED,
//					);
//
//					$this->conversationUserManager->add($user);
//				}
//			}
			// redirection vers connection
			return $this->redirectToRoute('connect', [
				'bindKey' => $this->security->crypt($this->security->buildBindkey($player->bind)),
			]);
		} catch (\Throwable $t) {
			// @TODO handle this
			throw $t;
			dd($t);
			// tentative de réparation de l'erreur
			return $this->redirectToRoute('create_character', [
				'step' => 'place-choice',
			]);
		}
	}

	private function createPlayerTechnology(Player $player, bool $isHighLevel): void
	{
		$technology = ($isHighLevel) ? new Technology(
			id: Uuid::v4(),
			player: $player,
			comPlatUnblock: 1,
			dock2Unblock: 1,
			dock3Unblock: 1,
			recyclingUnblock: 1,
			spatioportUnblock: 1,
			ship0Unblock: 1,
			ship1Unblock: 1,
			ship2Unblock: 1,
			ship3Unblock: 1,
			ship4Unblock: 1,
			ship5Unblock: 1,
			ship6Unblock: 1,
			ship7Unblock: 1,
			ship8Unblock: 1,
			ship9Unblock: 1,
			colonization: 1,
			conquest: 1,
			baseQuantity: 4,
		) : new Technology(id: Uuid::v4(), player: $player);

		$this->technologyRepository->save($technology);
	}
}
