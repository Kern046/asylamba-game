<?php

namespace App\Shared\Infrastructure\Controller;

use App\Classes\Container\ArrayList;
use App\Classes\Container\EventList;
use App\Classes\Library\Utils;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Zeus\Domain\Event\PlayerConnectionEvent;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class ConnectController extends AbstractController
{
	public function __construct(private readonly OrbitalBaseRepositoryInterface $orbitalBaseRepository)
	{
	}

	public function __invoke(
		Request $request,
		Security $security,
		PlayerRepositoryInterface $playerRepository,
		EntityManagerInterface $entityManager,
		EventDispatcherInterface $eventDispatcher,
		LoggerInterface $logger,
		int $playerId
	): Response {
		$session = $request->getSession();

		if (null === ($player = $playerRepository->get($playerId)) || !$player->canAccess()) {
			$logger->debug('Player not found or cannot access game');

			return $this->redirectToRoute('homepage');
		}
		$player->statement = Player::ACTIVE;

		$session->set('token', Utils::generateString(5));

		$this->createSession($session, $player);

		$security->login($player);

		// mise de dLastConnection + dLastActivity
		$player->dLastConnection = new \DateTimeImmutable();
		$player->dLastActivity = new \DateTimeImmutable();

		// confirmation au portail
		// TODO Replace when portal is implemented
		/*if ('enabled' === $this->getParameter('apimode')) {
			$api->confirmConnection($bindKey);
		}*/
		$entityManager->flush($player);

		$eventDispatcher->dispatch(new PlayerConnectionEvent($player));
		// redirection vers page de départ
		return $this->redirectToRoute('profile', [
			'mode' => ('splash' === $request->query->get('mode'))
				? 'profil/mode-splash'
				: 'profil',
		]);
	}

	private function createSession(Session $session, Player $player): void
	{
		// remplissage des données du joueur
		$session->set('playerId', $player->id);

		$playerBases = $this->orbitalBaseRepository->getPlayerBases($player);
		// remplissage des bonus

		// création des paramètres utilisateur
		$session->set('playerParams', new ArrayList());

		$session->set('playerInfo', new ArrayList());

		// remplissage des paramètres utilisateur
		$session->get('playerParams')->add('base', $playerBases[0]->id);

		// création des tableaux de données dans le contrôleur

		$session->set('playerEvent', new EventList());
	}
}
