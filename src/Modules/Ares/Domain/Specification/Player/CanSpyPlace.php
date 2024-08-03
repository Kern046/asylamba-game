<?php

namespace App\Modules\Ares\Domain\Specification\Player;

use App\Modules\Ares\Domain\Specification\PlaceHasPlayer;
use App\Modules\Ares\Domain\Specification\PlaceHavePlayersFaction;
use App\Modules\Ares\Domain\Specification\PlaceIsInhabited;
use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Specification\AndSpecification;
use App\Shared\Domain\Specification\NotSpecification;
use App\Shared\Domain\Specification\OrSpecification;

class CanSpyPlace extends OrSpecification
{
	public function __construct(Player $player)
	{
		parent::__construct(
			new AndSpecification(
				new PlaceHasPlayer(),
				new NotSpecification(new PlaceHavePlayersFaction($player)),
			),
			new AndSpecification(
				new NotSpecification(new PlaceHasPlayer()),
				new PlaceIsInhabited(),
			),
		);
	}
}
