<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Model;

enum CommanderMission: int
{
	case Move = 0;
	case Loot = 1;
	case Colo = 2;
	case Back = 3;
}
