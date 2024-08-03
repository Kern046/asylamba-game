<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Exception;

class NoCurrentBaseSetException extends \RuntimeException
{
	public function __construct()
	{
		parent::__construct('No current base set');
	}
}
