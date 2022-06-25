<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewOverview extends AbstractController
{
	public function __construct(
		private readonly CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		private readonly CommanderRepositoryInterface $commanderRepository,
		private readonly OrbitalBaseRepositoryInterface $orbitalBaseRepository,
	) {
	}

	public function __invoke(Player $currentPlayer): Response
	{
		return $this->render('pages/ares/fleet/overview.html.twig', [
			'obsets' => $this->getObsets($currentPlayer),
		]);
	}

	private function getObsets(Player $currentPlayer): array
	{
		// set d'orbitale base
		$obsets = [];
		foreach ($this->currentPlayerBasesRegistry->all() as $orbitalBase) {
			$obsets[] = [
				'info' => [
					'id' => $orbitalBase->id->toRfc4122(),
					'name' => $orbitalBase->name,
					'type' => $orbitalBase->typeOfBase,
				],
			];
		}

		// commander manager : yours
		$commanders = $this->commanderRepository->getPlayerCommanders(
			$currentPlayer,
			[Commander::AFFECTED, Commander::MOVING],
			['c.base' => 'DESC'],
		);

		for ($i = 0; $i < count($obsets); ++$i) {
			foreach ($commanders as $commander) {
				if ($commander->base->id->toRfc4122() === $obsets[$i]['info']['id']) {
					$obsets[$i]['fleets'][] = $commander;
				}
			}
		}
		// ship in dock
		$playerBases = $this->orbitalBaseRepository->getPlayerBases($currentPlayer);

		for ($i = 0; $i < count($obsets); ++$i) {
			foreach ($playerBases as $orbitalBase) {
				if ($orbitalBase->id->toRfc4122() == $obsets[$i]['info']['id']) {
					$obsets[$i]['dock'] = $orbitalBase->shipStorage;
				}
			}
		}

		return $obsets;
	}
}
