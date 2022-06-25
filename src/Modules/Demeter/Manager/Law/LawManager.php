<?php

namespace App\Modules\Demeter\Manager\Law;

use App\Modules\Demeter\Domain\Repository\Law\VoteLawRepositoryInterface;
use App\Modules\Demeter\Model\Law\Law;

class LawManager
{
	public function __construct(
		private readonly VoteLawRepositoryInterface $voteLawRepository,
	) {
	}

	public function ballot(Law $law): bool
	{
		$votes = $this->voteLawRepository->getLawVotes($law);

		$ballot = 0;

		foreach ($votes as $vote) {
			if ($vote->vote) {
				++$ballot;
			} else {
				--$ballot;
			}
		}

		return $ballot >= 0;
	}
}
