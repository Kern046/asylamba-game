<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base;

use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class SwitchBase extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBaseManager $orbitalBaseManager,
		OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		Uuid $baseId,
		string $page
	): Response {
		if (null === ($base = $orbitalBaseRepository->get($baseId))) {
			throw new NotFoundHttpException('Base not found');
		}

		if ($base->player->id !== $currentPlayer->id) {
			throw new AccessDeniedHttpException('This base does not belong to you');
		}

		$request->getSession()->get('playerParams')->add('base', $base->id);

		return $this->redirectToRoute(match ($page) {
			'generator' => 'generator',
			'refinery' => 'refinery',
			'docks' => 'docks',
			'technosphere' => 'technosphere',
			'commercialroute', 'sell', 'market' => 'trade_market',
			'school' => 'school',
			'spatioport' => 'spatioport',
			default => 'base_overview',
		}, $request->query->all());
	}
}
