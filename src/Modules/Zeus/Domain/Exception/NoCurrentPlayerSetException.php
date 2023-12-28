<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Domain\Exception;

class NoCurrentPlayerSetException extends \RuntimeException
{
	public function __construct()
	{
		parent::__construct('No current player has been set');
	}
}
