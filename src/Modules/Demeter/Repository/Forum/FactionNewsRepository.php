<?php

namespace App\Modules\Demeter\Repository\Forum;

use App\Classes\Library\Utils;
use App\Modules\Demeter\Domain\Repository\Forum\FactionNewsRepositoryInterface;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Forum\FactionNews;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class FactionNewsRepository extends DoctrineRepository implements FactionNewsRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, FactionNews::class);
	}

	public function get(Uuid $id): FactionNews|null
	{
		return $this->find($id);
	}

	public function getPinnedNew(Color $faction): FactionNews|null
	{
		return $this->findOneBy([
			'faction' => $faction,
			'pinned' => true,
		]);
	}

	public function getFactionNews(Color $faction): array
	{
		return $this->findBY([
			'faction' => $faction,
		]);
	}

	public function getFactionBasicNews(Color $faction): array
	{
		return $this->findBy([
			'faction' => $faction,
			'pinned' => false,
		]);
	}
}
