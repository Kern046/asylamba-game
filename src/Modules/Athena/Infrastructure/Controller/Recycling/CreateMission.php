<?php

namespace App\Modules\Athena\Infrastructure\Controller\Recycling;

use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\RecyclingMissionManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateMission extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		OrbitalBaseManager $orbitalBaseManager,
		OrbitalBaseHelper $orbitalBaseHelper,
		PlaceManager $placeManager,
		RecyclingMissionManager $recyclingMissionManager,
		int $targetId,
	): Response {
		$quantity = $request->request->getInt('quantity', 0);

		if ($quantity > 0) {
			$maxRecyclers = $orbitalBaseHelper->getInfo(OrbitalBaseResource::RECYCLING, 'level', $currentBase->levelRecycling, 'nbRecyclers');
			$usedRecyclers = 0;

			$baseMissions = $recyclingMissionManager->getBaseActiveMissions($currentBase->getId());

			foreach ($baseMissions as $mission) {
				$usedRecyclers += $mission->recyclerQuantity;
				$usedRecyclers += $mission->addToNextMission;
			}

			if ($maxRecyclers - $usedRecyclers >= $quantity) {
				if (($startPlace = $placeManager->get($currentBase->rPlace)) !== null && ($destinationPlace = $placeManager->get($targetId)) !== null) {
					if (null == $destinationPlace->rPlayer and in_array($destinationPlace->typeOfPlace, [2, 3, 4, 5])) {
						$travelTime = Game::getTimeToTravel($startPlace, $destinationPlace);

						if ($currentPlayer->rColor == $destinationPlace->sectorColor || ColorResource::NO_FACTION == $destinationPlace->sectorColor) {
							// create mission
							$rm = new RecyclingMission();
							$rm->rBase = $currentBase->getId();
							$rm->rTarget = $targetId;
							$rm->cycleTime = (2 * $travelTime) + RecyclingMission::RECYCLING_TIME;
							$rm->recyclerQuantity = $quantity;
							$rm->uRecycling = Utils::addSecondsToDate(Utils::now(), $rm->cycleTime);
							$rm->statement = RecyclingMission::ST_ACTIVE;

							$recyclingMissionManager->add($rm);

							$this->addFlash('success', 'Votre mission a été lancée.');

							return $this->redirect($request->headers->get('referer'));
						} else {
							throw new ErrorException('Vous pouvez recycler uniquement dans les secteurs de votre faction ainsi que dans les secteurs neutres.');
						}
					} else {
						throw new ErrorException('On ne peut pas recycler ce lieu, petit hacker.');
					}
				} else {
					throw new ErrorException('Il y a un problème avec le lieu de départ ou d\'arrivée. Veuillez contacter un administrateur.');
				}
			} else {
				throw new ErrorException('Vous n\'avez pas assez de recycleurs libres pour lancer cette mission.');
			}
		} else {
			throw new FormException('Ca va être dur de recycler avec autant peu de recycleurs. Entrez un nombre plus grand que zéro.');
		}
	}
}
