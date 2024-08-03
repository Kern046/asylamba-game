<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewMemorial extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		CommanderRepositoryInterface $commanderRepository,
	): Response {
		return $this->render('pages/ares/fleet/memorial.html.twig', [
			'commanders' => $commanderRepository->getPlayerCommanders($currentPlayer, [Commander::DEAD], ['c.palmares' => 'DESC']),
		]);
	}
}
