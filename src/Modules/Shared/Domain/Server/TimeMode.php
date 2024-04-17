<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Server;

enum TimeMode: string
{
	case Standard = 'standard';
	case Fast = 'fast';
	public function isStandard(): bool
	{
		return self::Standard === $this;
	}

	public function isFast(): bool
	{
		return self::Fast === $this;
	}
}
