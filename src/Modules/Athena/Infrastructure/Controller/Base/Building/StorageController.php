<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class StorageController extends AbstractController
{
	public function __invoke(CurrentPlayerBonusRegistry $currentPlayerBonusRegistry): Response
	{
		return $this->render('pages/athena/storage.html.twig', [
			'storage_bonus' => $currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::REFINERY_STORAGE),
		]);
	}
}
