<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\Repository;

use App\Modules\Hermes\Domain\Repository\ConversationUserRepositoryInterface;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationUserRepository extends DoctrineRepository implements ConversationUserRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, ConversationUser::class);
	}
}
