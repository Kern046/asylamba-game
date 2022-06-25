<?php

namespace App\Modules\Hermes\Infrastructure\Repository;

use App\Modules\Hermes\Domain\Repository\ConversationMessageRepositoryInterface;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<ConversationMessage>
 */
class ConversationMessageRepository extends DoctrineRepository implements ConversationMessageRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, ConversationMessage::class);
	}
}
