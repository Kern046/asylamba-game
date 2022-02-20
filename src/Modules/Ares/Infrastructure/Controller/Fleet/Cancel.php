<?php

namespace App\Modules\Ares\Infrastructure\Controller\Fleet;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Utils;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

class Cancel extends AbstractController
{
	public function __invoke(
		Request $request,
		MessageBusInterface $messageBus,
		Player $currentPlayer,
		CommanderManager $commanderManager,
		EntityManager $entityManager,
		int $id,
	): Response {

		if (($commander = $commanderManager->get($id)) === null || $commander->rPlayer !== $currentPlayer->getId()) {
			throw new ErrorException('Ce commandant ne vous appartient pas ou n\'existe pas.');
		}
		if ($commander->travelType == Commander::BACK) {
			throw new ErrorException('Vous ne pouvez pas annuler un retour.');
		}
		// @TODO travel cancellation
		//$scheduler->cancel($commander, $commander->dArrival);

		$interval = Utils::interval($commander->dArrival, Utils::now(), 's');
		$dStart = new \DateTime(Utils::now());
		$dStart->modify('-' . $interval . ' second');

		$duration = Utils::interval($commander->dStart, $commander->dArrival, 's');

		$dArrival = new \DateTime($dStart->format('Y-m-d H:i:s'));
		$dArrival->modify('+' . $duration . ' second');

		$rDestinationPlace = $commander->rDestinationPlace;
		$commander->rDestinationPlace = $commander->rStartPlace;
		$commander->rStartPlace = $rDestinationPlace;
		$startPlaceName = $commander->startPlaceName;
		$commander->startPlaceName = $commander->destinationPlaceName;
		$commander->destinationPlaceName = $startPlaceName;
		$commander->dStart = $dStart->format('Y-m-d H:i:s');
		$commander->dArrival = $dArrival->format('Y-m-d H:i:s');
		$commander->travelType = Commander::BACK;

		$this->addFlash('success', 'Déplacement annulé.');

		$messageBus->dispatch(
			new \App\Modules\Ares\Message\CommanderTravelMessage($commander->getId()),
			[\App\Classes\Library\DateTimeConverter::to_delay_stamp($commander->getArrivalDate())],
		);
		$entityManager->flush();
		if ($request->query->has('redirect')) {
			return $this->redirectToRoute('map', ['place' => $request->query->get('redirect')]);
		}
		return $this->redirect($request->headers->get('referer'));
	}
}
