<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Route;

use App\Classes\Library\Format;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Hermes\Application\Builder\NotificationBuilder;
use App\Modules\Hermes\Domain\Repository\NotificationRepositoryInterface;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Refuse extends AbstractController
{
	public function __invoke(
		Request                            $request,
		Player                             $currentPlayer,
		OrbitalBase                        $currentBase,
		CommercialRouteRepositoryInterface $commercialRouteRepository,
		PlayerRepositoryInterface          $playerRepository,
		PlayerManager                      $playerManager,
		NotificationRepositoryInterface    $notificationRepository,
		Uuid                               $baseId,
		Uuid                               $id,
	): Response {
		$cr = $commercialRouteRepository->getByIdAndDistantBase($id, $currentBase)
			?? throw $this->createNotFoundException('Commercial route not found');

		if (!$cr->isProposed()) {
			throw new ConflictHttpException('Commercial route has already been established');
		}
		$proposerBase = $cr->originBase;
		$refusingBase = $cr->destinationBase;

		// rend les crédits au proposant
		$playerManager->increaseCredit($proposerBase->player, $cr->price);

		// notification
		$notification = NotificationBuilder::new()
			->setTitle('Route commerciale refusée')
			->setContent(NotificationBuilder::paragraph(
				NotificationBuilder::link(
					$this->generateUrl('embassy', ['player' => $currentPlayer->id]),
					$currentPlayer->name,
				),
				' a refusé la route commerciale proposée entre ',
				NotificationBuilder::link(
					$this->generateUrl('map', ['place' => $refusingBase->place->id]),
					$refusingBase->name,
				),
				' et ',
				NotificationBuilder::link(
					$this->generateUrl('map', ['place' => $proposerBase->place->id]),
					$proposerBase->name,
				),
				'.',
				NotificationBuilder::divider(),
				'Les ',
				Format::numberFormat($cr->price),
				' crédits bloqués sont à nouveau disponibles.',
			))
			->for($proposerBase->player);

		$notificationRepository->save($notification);

		// destruction de la route
		$commercialRouteRepository->remove($cr);

		$this->addFlash('success', 'Route commerciale refusée');

		return $this->redirect($request->headers->get('referer'));
	}
}
