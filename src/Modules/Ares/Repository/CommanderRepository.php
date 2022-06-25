<?php

namespace App\Modules\Ares\Repository;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Model\Place;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Commander>
 */
class CommanderRepository extends DoctrineRepository implements CommanderRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Commander::class);
	}

	public function get(Uuid $id): Commander|null
	{
		return $this->find($id);
	}

	public function getAllByStatements(array $statements): array
	{
		$qb = $this->createQueryBuilder('c');

		return $qb
			->andWhere($qb->expr()->in('c.statement', $statements))
			->getQuery()
			->getResult();
	}

	public function getCommandersByIds(array $ids = []): array
	{
		$qb = $this->createQueryBuilder('c');

		return $qb
			->andWhere($qb->expr()->in('c.id', $ids))
			->getQuery()
			->getResult();
	}

	public function getMovingCommanders(): array
	{
		return $this->findBy(['statement' => Commander::MOVING]);
	}

	public function getPlayerCommanders(Player $player, array $statements = [], array $orderBy = []): array
	{
		$qb = $this->createQueryBuilder('c');

		foreach ($orderBy as $field => $order) {
			$qb->addOrderBy($field, $order);
		}

		return $qb
			->andWhere($qb->expr()->in('c.statement', ':statements'))
			->andWhere('c.player = :player')
			->setParameter('statements', $statements)
			->setParameter('player', $player)
			->getQuery()
			->getResult();
	}

	public function getBaseCommanders(OrbitalBase $orbitalBase, array $statements = [], array $orderBy = []): array
	{
		return $this->findBy([
			'base' => $orbitalBase,
			'statement' => $statements,
		], $orderBy);
	}

	public function getCommandersByLine(OrbitalBase $orbitalBase, int $line): array
	{
		return $this->findBy([
			'base' => $orbitalBase,
			'line' => $line,
		]);
	}

	public function getIncomingAttacks(Player $player): array
	{
		$qb = $this->createQueryBuilder('c');

		return $qb
			->join('c.destinationPlace', 'dp')
			->join('dp.player', 'player')
			->andWhere('c.player = :player')
			->andWhere($qb->expr()->eq('c.statement', Commander::MOVING))
			->andWhere($qb->expr()->in('c.travelType', [Commander::COLO, Commander::LOOT]))
			->setParameter('player', $player)
			->getQuery()
			->getResult();
	}

	public function getOutcomingAttacks(Player $player): array
	{
		return $this->findBy([
			'player' => $player,
			'statement' => Commander::MOVING,
		]);
	}

	public function getIncomingCommanders(Place $place): array
	{
		return $this->findBy([
			'destinationPlace' => $place,
			'statement' => Commander::MOVING,
		], ['dArrival' => 'ASC']);
	}

	public function countCommandersByLine(OrbitalBase $orbitalBase, int $line): int
	{
		$qb = $this->createQueryBuilder('c');

		return $qb
			->select('COUNT(c)')
			->andWhere('c.base = :orbital_base')
			->andWhere('c.line = :line')
			->andWhere($qb->expr()->in('c.statement', [Commander::AFFECTED, Commander::MOVING]))
			->setParameter('orbital_base', $orbitalBase->id, UuidType::NAME)
			->setParameter('line', $line)
			->getQuery()
			->getSingleScalarResult();
	}

	public function getFactionCommanderStats(int $factionId): array
	{
		return $this->getEntityManager()->getConnection()->fetchAllAssociative(
			'SELECT
				COUNT(c.id) AS nb,
				AVG(c.level) AS avgLevel
			FROM commander AS c
				LEFT JOIN player AS p
				ON c.player = p.id
			WHERE p.rColor = ? AND (c.statement = ? OR c.statement = ?)',
			[$factionId, Commander::AFFECTED, Commander::MOVING],
		);
	}

	public function getFactionFleetStats(int $factionId): array
	{
		return $this->getEntityManager()->getConnection()->fetchAllAssociative(
			'SELECT
				SUM(s.ship0) AS nbs0,
				SUM(s.ship1) AS nbs1,
				SUM(s.ship2) AS nbs2,
				SUM(s.ship3) AS nbs3,
				SUM(s.ship4) AS nbs4,
				SUM(s.ship5) AS nbs5,
				SUM(s.ship6) AS nbs6,
				SUM(s.ship7) AS nbs7,
				SUM(s.ship8) AS nbs8,
				SUM(s.ship9) AS nbs9,
				SUM(s.ship10) AS nbs10,
				SUM(s.ship11) AS nbs11
			FROM squadron AS s
				LEFT JOIN commander AS c
				ON s.rCommander = c.id
				LEFT JOIN player AS p
				ON c.player = p.id
			WHERE p.rColor = ? AND (c.statement = ? OR c.statement = ?)',
			[$factionId, Commander::AFFECTED, Commander::MOVING],
		);
	}
}
