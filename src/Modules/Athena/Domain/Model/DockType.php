<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Model;

enum DockType: string
{
	case Factory = 'factory';
	case Shipyard = 'shipyard';
}
