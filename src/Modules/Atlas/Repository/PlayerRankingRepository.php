<?php

namespace App\Modules\Atlas\Repository;

use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Report;
use App\Modules\Atlas\Domain\Repository\PlayerRankingRepositoryInterface;
use App\Modules\Atlas\Model\PlayerRanking;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\DBAL\Result;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * TODO build ranking statement with queryBuilder
 */
class PlayerRankingRepository extends DoctrineRepository implements PlayerRankingRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, PlayerRanking::class);
	}

	public function getFactionPlayerRankings(Ranking $ranking, Color $faction): array
	{
		$qb = $this->createQueryBuilder('pr');

		$qb
			->join('pr.player', 'p')
			->where('p.faction = :faction')
			->andWhere('pr.ranking = :ranking')
			->setParameter('faction', $faction)
			->setParameter('ranking', $ranking);

		return $qb->getQuery()->getResult();
	}

	public function getPlayerLastRanking(Player $player): PlayerRanking|null
	{
		$qb = $this->createQueryBuilder('pr');

		$qb
			->orderBy('pr.createdAt', 'DESC')
			->where('pr.player = :player')
			->setParameter('player', $player)
			->setMaxResults(1);

		return $qb->getQuery()->getOneOrNullResult();
	}

	public function getBestPlayerRanking(): PlayerRanking|null
	{
		return $this->findOneBy([], [
			'generalPosition' => 'DESC',
		]);
	}

	public function getAttackersButcherRanking(): Result
	{
		return $this->getEntityManager()->getConnection()->executeQuery(
			<<<SQL
				SELECT
					p.id AS player_id,
					SUM(r.attacker_pev_at_beginning) - SUM(r.attacker_pev_at_end) AS lostPEV,
					SUM(r.defender_pev_at_beginning) - SUM(r.defender_pev_at_end) AS destroyedPEV
				FROM report r
				LEFT JOIN player p ON r.attacker_id = p.id
				WHERE p.statement IN (:statements)
				GROUP BY p.id
				ORDER BY p.id
			SQL,
			['statements' => implode(',',[Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY])],
		);
	}

	public function getDefendersButcherRanking(): Result
	{
		return $this->getEntityManager()->getConnection()->executeQuery(
			<<<SQL
				SELECT
					p.id as player_id,
					(SUM(r.defender_pev_at_beginning) - SUM(r.defender_pev_at_end)) AS lostPEV,
					(SUM(r.attacker_pev_at_beginning) - SUM(r.attacker_pev_at_end)) AS destroyedPEV,
					(SUM(r.defender_pev_at_beginning) - SUM(r.defender_pev_at_end) - SUM(r.attacker_pev_at_beginning) - SUM(r.attacker_pev_at_end)) AS score
				FROM report r
				LEFT JOIN player p ON r.defender_id = p.id
				WHERE p.statement IN (:statements)
				GROUP BY p.id
				ORDER BY p.id
			SQL,
			['statements' => implode(',', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY])],
		);
	}

	public function getRankingsByRange(Ranking $ranking, string $field, int $offset, int $limit): array
	{
		return $this->findBy([
			'ranking' => $ranking,
		], [$field => 'ASC'], $limit, $offset);
	}

	public function getPlayersResources(): Result
	{
		return $this->getEntityManager()->getConnection()->executeQuery(
			'SELECT p.id AS player,
				ob.levelRefinery AS levelRefinery,
				pl.coefResources AS coefResources
			FROM orbitalBase AS ob 
			LEFT JOIN place AS pl
				ON pl.id = ob.place_id
			LEFT JOIN player AS p
				on p.id = ob.player_id
			WHERE p.statement IN ('.implode(',', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]).')'
		);
	}

	public function getPlayersResourcesData(): Result
	{
		return $this->getEntityManager()->getConnection()->executeQuery(
			'SELECT 
				p.id AS player,
				SUM(ob.resourcesStorage) AS sumResources
			FROM orbitalBase AS ob 
			LEFT JOIN player AS p
				on p.id = ob.player_id
			WHERE p.statement IN ('.implode(',', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]).')
			GROUP BY ob.place_id'
		);
	}

	public function getPlayersGeneralData(): Result
	{
		return $this->getEntityManager()->getConnection()->executeQuery(
			'SELECT 
				p.id AS player,
				SUM(ob.points) AS points,
				SUM(ob.resourcesStorage) AS resources,
				ob.ship_storage
			FROM orbitalBase AS ob 
			LEFT JOIN player AS p
				ON p.id = ob.player_id
			WHERE p.statement IN ('.implode(',', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]).')
			GROUP BY p.id'
		);
	}

	public function getPlayersArmiesData(): Result
	{
		return $this->getEntityManager()->getConnection()->executeQuery(
			'SELECT 
				p.id AS player,
				SUM(sq.ship0) as s0,
				SUM(sq.ship1) as s1,
				SUM(sq.ship2) as s2,
				SUM(sq.ship3) as s3,
				SUM(sq.ship4) as s4,
				SUM(sq.ship5) as s5,
				SUM(sq.ship6) as s6,
				SUM(sq.ship7) as s7,
				SUM(sq.ship8) as s8,
				SUM(sq.ship9) as s9,
				SUM(sq.ship10) as s10,
				SUM(sq.ship11) as s11
			FROM squadron AS sq 
			LEFT JOIN commander AS c
				ON c.id = sq.commander_id
			LEFT JOIN player AS p
				ON p.id = c.player_id
			WHERE c.statement IN ('.implode(',', [Commander::AFFECTED, Commander::MOVING]).')
			GROUP BY p.id'
		);
	}

	public function getPlayersPlanetData(): Result
	{
		return $this->getEntityManager()->getConnection()->executeQuery(
			'SELECT 
				p.id AS player,
				COUNT(ob.place_id) AS sumPlanets
			FROM orbitalBase AS ob
			LEFT JOIN player AS p
				on p.id = ob.player_id
			WHERE p.statement IN ('.implode(',', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]).')
			GROUP BY ob.place_id'
		);
	}

	public function getPlayersTradeRoutes(): Result
	{
		return $this->getEntityManager()->getConnection()->executeQuery(
			'SELECT 
				p.id AS player,
				SUM(c.income) AS income
			FROM commercialRoute AS c
			LEFT JOIN orbitalBase AS o ON o.id = c.origin_base_id
			RIGHT JOIN player AS p ON p.id = o.player_id
			WHERE p.statement IN ('.implode(',', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]).')
			GROUP BY p.id
			ORDER BY p.id'
		);
	}

	public function getPlayersLinkedTradeRoutes(): Result
	{
		return $this->getEntityManager()->getConnection()->executeQuery(
			'SELECT 
				p.id AS player,
				SUM(income) AS income
			FROM `commercialRoute` AS c
			LEFT JOIN orbitalBase AS o
				ON o.id = c.destination_base_id
			RIGHT JOIN player AS p
				ON p.id = o.player_id
			WHERE p.statement IN ('.implode(',', [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]).')
			GROUP BY p.id
			ORDER BY p.id'
		);
	}
}
