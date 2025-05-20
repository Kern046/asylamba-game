<?php

namespace App\Modules\Zeus\Domain\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\CreditHolderInterface;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Collections\Collection;

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

	/**
	 * @return list<CreditTransaction>
	 */
	public function getAllByFactionReceiverFromMembers(Color $faction): array;

	/**
	 * @return list<CreditTransaction>
	 */
	public function getAllByFactionReceiverFromFactions(Color $faction): array;

	/**
	 * @return Collection<CreditTransaction>
	 */
	public function matchAllByPlayerSince(Player $player, \DateTimeImmutable $since): Collection;
}
