<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Model;

interface SystemUpdatable
{
	public function lastUpdatedBySystemAt(): \DateTimeImmutable;
}
