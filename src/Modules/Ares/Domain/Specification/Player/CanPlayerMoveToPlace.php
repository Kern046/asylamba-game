<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Gaia\Model\Place;
use App\Modules\Zeus\Model\Player;

class CanPlayerMoveToPlace extends PlayerSpecification
{
	public function __construct(Player $player, protected OrbitalBase $orbitalBase)
	{
		parent::__construct($player);
	}

	/**
	 * @param Place $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return null !== $candidate->player
			&& (($candidate->player->id === $this->player->id && $candidate->id !== $this->orbitalBase->player->id)
			|| $candidate->player->faction->id === $this->player->faction->id);
	}
}
