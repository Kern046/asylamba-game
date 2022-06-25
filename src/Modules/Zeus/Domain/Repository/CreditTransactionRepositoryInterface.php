<?php

namespace App\Modules\Zeus\Domain\Repository;

use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\CreditHolderInterface;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;

/**
 * @extends EntityRepositoryInterface<CreditTransaction>
 */
interface CreditTransactionRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<CreditTransaction>
	 */
	public function getAllBySender(CreditHolderInterface $sender): array;

	/**
	 * @return list<CreditTransaction>
	 */
	public function getAllByPlayerReceiver(Player $player): array;
}
