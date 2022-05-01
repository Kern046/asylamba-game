<?php

namespace App\Shared\Infrastructure\Controller;

use App\Classes\Container\ArrayList;
use App\Classes\Container\EventList;
use App\Classes\Entity\EntityManager;
use App\Classes\Library\Security;
use App\Classes\Library\Utils;
use App\Classes\Worker\API;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Zeus\Domain\Event\PlayerConnectionEvent;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class ConnectController extends AbstractController
{
	public function __invoke(
		Request $request,
		Security $security,
		PlayerManager $playerManager,
		PlayerBonusManager $playerBonusManager,
		API $api,
		OrbitalBaseManager $orbitalBaseManager,
		EntityManager $entityManager,
		EventDispatcherInterface $eventDispatcher,
		string $bindKey
	): Response {
		$session = $request->getSession();

		// extraction du bindkey
		$query = $security->uncrypt($bindKey);
		$bindKey = $security->extractBindKey($query);
		$time = $security->extractTime($query);

		// vérification de la validité du bindkey
		if (abs((int) $time - time()) > 300) {
			return $this->redirectToRoute('homepage');
		}

		if (null === ($player = $playerManager->getByBindKey($bindKey))
			|| !\in_array($player->getStatement(), [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY])) {
			return $this->redirectToRoute('homepage');
		}
		$player->setStatement(Player::ACTIVE);

		$session->set('token', Utils::generateString(5));

		$this->createSession($session, $playerBonusManager, $orbitalBaseManager, $player);

		// mise de dLastConnection + dLastActivity
		$player->setDLastConnection(Utils::now());
		$player->setDLastActivity(Utils::now());

		// confirmation au portail
		if ('enabled' === $this->getParameter('apimode')) {
			$api->confirmConnection($bindKey);
		}
		$entityManager->flush($player);

		$eventDispatcher->dispatch(new PlayerConnectionEvent($player));
		// redirection vers page de départ
		return $this->redirectToRoute('profile', [
			'mode' => ('splash' === $request->query->get('mode'))
				? 'profil/mode-splash'
				: 'profil',
		]);
	}

	private function createSession(
		Session $session,
		PlayerBonusManager $playerBonusManager,
		OrbitalBaseManager $orbitalBaseManager,
		Player $player,
	): void {
		// remplissage des données du joueur
		$session->set('playerId', $player->getId());

		$playerBases = $orbitalBaseManager->getPlayerBases($player->getId());
		// remplissage des bonus

		// création des paramètres utilisateur
		$session->set('playerParams', new ArrayList());

		// remplissage des paramètres utilisateur
		$session->get('playerParams')->add('base', $playerBases[0]->getId());

		// création des tableaux de données dans le contrôleur

		$session->set('playerEvent', new EventList());
	}
}
