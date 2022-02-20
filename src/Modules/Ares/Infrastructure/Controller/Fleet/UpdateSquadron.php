<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Helper\TutorialHelper;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateSquadron extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBaseManager $orbitalBaseManager,
		CommanderManager $commanderManager,
		TutorialHelper $tutorialHelper,
		EntityManager $entityManager,
		int $id,
		int $squadronId,
	): Response {
		$payload = $request->toArray();

		if (empty($payload['army']) || empty($payload['base_id'])) {
			throw new FormException('Pas assez d\'informations pour assigner un vaisseau.');
		}

		$newSquadron = array_map(fn($el) => $el > 0 ? (int) $el : 0, $payload['army']);

		if (count($newSquadron) !== 12) {
			throw new FormException('Pas assez d\'informations pour assigner un vaisseau.');
		}

		$commander = $commanderManager->get($id);
		$base = $orbitalBaseManager->get($payload['base_id']);

		if ($commander === null || $base === null || $commander->rBase !== $base->getId() || $commander->statement !== Commander::AFFECTED) {
			throw new ErrorException('Erreur dans les références du commandant ou de la base.');
		}
		$squadron = $commander->getSquadron($squadronId);

		if ($squadron === false) {
			throw new ErrorException('Erreur dans les références du commandant ou de la base.');
		}

		$squadronSHIP = $squadron->arrayOfShips;
		$baseSHIP = $base->shipStorage;

		foreach ($newSquadron as $i => $v) {
			$baseSHIP[$i] -= ($v - $squadronSHIP[$i]);
			$squadronSHIP[$i] = $v;
		}

# token de vérification
		$baseOK = TRUE;
		$squadronOK = TRUE;
		$totalPEV = 0;
# vérif shipStorage (pas de nombre négatif)
		foreach ($baseSHIP as $i => $v) {
			if ($v < 0) {
				$baseOK = FALSE;
				break;
			}
		}

# vérif de squadron (pas plus de 100 PEV, pas de nombre négatif)
		foreach ($squadronSHIP as $i => $v) {
			$totalPEV += $v * ShipResource::getInfo($i, 'pev');
			if ($v < 0) {
				$squadronOK = FALSE;
				break;
			}
		}

		if (!$baseOK || !$squadronOK || $totalPEV > 100) {
			throw new ErrorException('Erreur dans la répartition des vaisseaux.');
		}
# tutorial
		if ($currentPlayer->stepDone === false && $currentPlayer->getStepTutorial() === TutorialResource::FILL_SQUADRON) {
			$tutorialHelper->setStepDone();
		}

		$base->shipStorage = $baseSHIP;
		$commander->getSquadron($squadronId)->arrayOfShips = $squadronSHIP;

		$entityManager->flush();

		return new Response('', Response::HTTP_NO_CONTENT);
	}
}
