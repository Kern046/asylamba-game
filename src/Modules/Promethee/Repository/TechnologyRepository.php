<?php

declare(strict_types=1);

namespace App\Modules\Promethee\Repository;

use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;

class TechnologyRepository extends DoctrineRepository implements TechnologyRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Technology::class);
	}

	public function getPlayerTechnology(Player $player): Technology
	{
		return $this->findOneBy([
			'player' => $player,
		]);
	}
}
