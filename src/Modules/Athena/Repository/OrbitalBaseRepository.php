<?php

namespace App\Modules\Athena\Repository;

use App\Classes\Entity\AbstractRepository;
use App\Classes\Library\Utils;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Model\Place;
use App\Modules\Gaia\Model\Sector;
use App\Modules\Gaia\Model\System;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<OrbitalBase>
 */
class OrbitalBaseRepository extends DoctrineRepository implements OrbitalBaseRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, OrbitalBase::class);
	}

	public function get(Uuid $id): OrbitalBase|null
	{
		return $this->find($id);
	}

	public function getPlaceBase(Place $place): OrbitalBase|null
	{
		return $this->findOneBy([
			'place' => $place,
		]);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}

	public function getPlayerBases(Player $player): array
	{
		return $this->findBy([
			'player' => $player,
		], [
			'createdAt' => 'ASC',
		]);
	}

	public function getPlayerBasesCount(Player $player): int
	{
		return $this->count([
			'player' => $player,
		]);
	}

	public function getSectorBases(Sector $sector): array
	{
		$qb = $this->createQueryBuilder('ob');

		return $qb
			->join('ob.place', 'place')
			->join('place.system', 'system')
			->where('system.sector = :sector')
			->setParameter('sector', $sector)
			->getQuery()
			->getResult();
	}

	public function getSystemBases(System $system): array
	{
		$qb = $this->createQueryBuilder('ob');

		return $qb
			->join('ob.place', 'place')
			->where('IDENTITY(place.system) = :system_id')
			->setParameter('system_id', $system->id->toBinary())
			->getQuery()
			->getResult();
	}

	public function getPlayerBase(int $id, Player $player): OrbitalBase|null
	{
		return $this->findOneBy([
			'id' => $id,
			'player' => $player,
		]);
	}
}
