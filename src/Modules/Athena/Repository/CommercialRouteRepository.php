<?php

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<CommercialRoute>
 */
class CommercialRouteRepository extends DoctrineRepository implements CommercialRouteRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, CommercialRoute::class);
	}

	public function get($id): CommercialRoute|null
	{
		return $this->find($id);
	}

	/**
	 * @param list<int> $factions
	 *
	 * @throws Exception
	 */
	public function searchCandidates(Player $player, OrbitalBase $orbitalBase, array $factions, int $minDistance, int $maxDistance): array
	{
		$factionIdentifiers = sprintf("(%s)", implode(',', $factions));

		return $this->getEntityManager()->getConnection()->fetchAllAssociative(
			<<<SQL
				SELECT
				faction.identifier AS factionIdentifier,
				player.avatar AS playerAvatar,
				player.name AS playerName,
				ob.name AS baseName,
				ob.place_id AS placeId,
				(FLOOR(SQRT(POW(:system_x - s.xPosition, 2) + POW(:system_y - s.yPosition, 2)))) AS distance
				FROM orbitalBase AS ob
				INNER JOIN player ON ob.player_id = player.id
				INNER JOIN color faction ON faction.id = player.faction_id
				INNER JOIN place AS p ON ob.place_id = p.id
				INNER JOIN system AS s ON p.system_id = s.id
				INNER JOIN sector AS se ON s.sector_id = se.id
				WHERE player.id != :player_id
				AND ob.levelSpatioport > 0
				AND (FLOOR(SQRT(POW(:system_x - s.xPosition, 2) + POW(:system_y - s.yPosition, 2)))) >= :min_distance
				AND (FLOOR(SQRT(POW(:system_x - s.xPosition, 2) + POW(:system_y - s.yPosition, 2)))) <= :max_distance
				AND faction.identifier IN $factionIdentifiers
				ORDER BY distance DESC
				LIMIT 40
			SQL,
			[
				'system_x' => $orbitalBase->place->system->xPosition,
				'system_y' => $orbitalBase->place->system->yPosition,
				'player_id' => $player->id,
				'min_distance' => $minDistance,
				'max_distance' => $maxDistance,
			],
		);
	}

	/**
	 * @throws Exception
	 */
	public function getAllPlayerRoutes(Player $player): array
	{
		$qb = $this->createQueryBuilder('cr');

		$qb
			->select()
			->leftJoin('cr.originBase', 'ob')
			->join('ob.place', 'obp')
			->leftJoin('cr.destinationBase', 'db')
			->join('db.place', 'dbp')
			->where('db.player = :player')
			->orWhere('ob.player = :player')
			->setParameter('player', $player);

		return $qb->getQuery()->getResult();
	}

	/**
	 * @throws Exception
	 */
	public function countCommercialRoutesBetweenFactions(Color $faction, Color $otherFaction): int
	{
		return $this->getEntityManager()->getConnection()->fetchNumeric(
			<<<SQL
				SELECT
					COUNT(cr.id) AS nb
				FROM commercialRoute AS cr
				LEFT JOIN orbitalBase AS ob1
					ON cr.originBase = ob1.rPlace
				LEFT JOIN player AS pl1
					ON ob1.player = pl1.id
				LEFT JOIN orbitalBase AS ob2
					ON cr.destinationBase = ob2.rPlace
				LEFT JOIN player AS pl2
					ON ob2.player = pl2.id
				WHERE (
				    (pl1.faction = :faction AND pl2.faction = :other_faction)
					OR (pl1.faction = :other_faction AND pl2.faction = :faction)
				) AND cr.statement = :statement
			SQL,
			[
				'faction' => $faction,
				'other_faction' => $otherFaction,
				'statement' => CommercialRoute::ACTIVE,
			],
		);
	}

	public function getCommercialRouteFactionData(Color $faction): array
	{
		return $this->getEntityManager()->getConnection()->fetchAssociative(
			<<<SQL
				SELECT
					COUNT(cr.id) AS nb,
					SUM(cr.income) AS income
				FROM commercialRoute AS cr
				LEFT JOIN orbitalBase AS ob1
					ON cr.originBase = ob1.place
				LEFT JOIN player AS pl1
					ON ob1.player = pl1.id
				LEFT JOIN orbitalBase AS ob2
					ON cr.destinationBase = ob2.rPlace
				LEFT JOIN player AS pl2
					ON ob2.player = pl2.id
				WHERE (pl1.faction = :faction OR pl2.faction = :faction)
					AND cr.statement = :statement
			SQL,
			[
				'faction' => $faction,
				'statement' => CommercialRoute::ACTIVE,
			],
		);
	}

	public function getByIdAndBase(Uuid $id, OrbitalBase $base): CommercialRoute|null
	{
		return $this->findOneBy([
			'id' => $id,
			'originBase' => $base,
		]);
	}

	public function getByIdAndDistantBase(Uuid $id, OrbitalBase $base): CommercialRoute|null
	{
		return $this->findOneBy([
			'id' => $id,
			'destinationBase' => $base,
		]);
	}

	/**
	 * @return list<CommercialRoute>
	 */
	public function getByBase(OrbitalBase $base): array
	{
		return $this->findBy([
			'originBase' => $base,
		]);
	}

	/**
	 * @return list<CommercialRoute>
	 */
	public function getByDistantBase(OrbitalBase $base): array
	{
		return $this->findBy([
			'destinationBase' => $base,
		]);
	}

	public function getBaseRoutes(OrbitalBase $base): array
	{
		$qb = $this->createQueryBuilder('cr');

		return $qb
			->where($this->getBaseEndpointsExpr($qb))
			->setParameter('base', $base->id, UuidType::NAME)
			->getQuery()
			->getResult();
	}

	public function getExistingRoute(OrbitalBase $base, OrbitalBase $distantBase): CommercialRoute|null
	{
		$qb = $this->createQueryBuilder('cr');

		return $qb
			->where($this->getBoundBasesStatement($qb))
			->setParameter('base', $base)
			->setParameter('distant_base', $distantBase)
			->getQuery()
			->getOneOrNullResult();
	}

	/**
	 * @throws NonUniqueResultException
	 * @throws NoResultException
	 */
	public function getBaseIncome(OrbitalBase $base): int
	{
		$qb = $this->createQueryBuilder('cr');

		return $qb
			->select('SUM(cr.income) AS total_income')
			->where(
				$qb->expr()->andX(
					$this->getBaseEndpointsExpr($qb),
					$qb->expr()->eq('cr.statement', CommercialRoute::ACTIVE),
				),
			)
			->setParameter('base', $base)
			->getQuery()
			->getSingleScalarResult() ?? 0;
	}

	/**
	 * @throws NonUniqueResultException
	 * @throws NoResultException
	 */
	public function countBaseRoutes(Orbitalbase $base, array $statements = []): int
	{
		$qb = $this->createQueryBuilder('cr');

		return $qb
			->select('COUNT(cr.id) AS nb_routes')
			->where(
				$qb->expr()->andX(
					$this->getBaseEndpointsExpr($qb),
					([] !== $statements) ? $qb->expr()->in('cr.statement', $statements) : null,
				),
			)
			->setParameter('base', $base)
			->getQuery()
			->getSingleScalarResult();
	}

	public function freezeRoutes(Color $faction, Color $otherFaction, bool $freeze): void
	{
		$qb = $this->createQueryBuilder('cr');

		$qb
			->update('cr')
			->set('cr.statement', (true === $freeze) ? CommercialRoute::STANDBY : CommercialRoute::ACTIVE)
			->leftJoin('cr.originBase', 'ob1')
			->leftJoin('ob1.player', 'pl1')
			->leftJoin('cr.destinationBase', 'ob2')
			->leftJoin('ob2.player', 'pl2')
			->where($qb->expr()->andX(
				$qb->expr()->orX(
					$qb->expr()->andX(
						$qb->expr()->eq('pl1.faction', ':faction'),
						$qb->expr()->eq('pl2.faction', ':other_faction'),
					),
					$qb->expr()->andX(
						$qb->expr()->eq('pl1.faction', ':other_faction'),
						$qb->expr()->eq('pl2.faction', ':faction'),
					),
				),
				$qb->expr()->eq(
					'cr.statement',
					(true === $freeze) ? CommercialRoute::ACTIVE : CommercialRoute::STANDBY,
				),
			))
			->getQuery()->execute([
				'faction' => $faction,
				'other_faction' => $otherFaction,
			]);
	}

	private function getBoundBasesStatement(QueryBuilder $qb): Expr\Orx
	{
		return $qb->expr()->orX(
			$qb->expr()->andX(
				$qb->expr()->eq('cr.originBase', ':base'),
				$qb->expr()->eq('cr.destinationBase', ':distant_base'),
			),
			$qb->expr()->andX(
				$qb->expr()->eq('cr.originBase', ':distant_base'),
				$qb->expr()->eq('cr.destinationBase', ':base'),
			),
		);
	}

	private function getBaseEndpointsExpr(QueryBuilder $qb): Expr\Orx
	{
		return $qb->expr()->orX(
			$qb->expr()->eq('cr.originBase', ':base'),
			$qb->expr()->eq('cr.destinationBase', ':base'),
		);
	}
}
