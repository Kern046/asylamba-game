<?php

namespace App\Modules\Ares\Domain\Model;

enum ShipStat: string
{
	case Name = 'name';
	case CodeName = 'codeName';
	case Life = 'life';
	case Attack = 'attack';
	case Defense = 'defense';
	case Speed = 'speed';
	case Pev = 'pev';
}
