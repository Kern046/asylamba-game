<?php

namespace App\Modules\Athena\Infrastructure\Validator\DTO;

use App\Modules\Athena\Model\OrbitalBase;

interface HasBase
{
	public function getBase(): OrbitalBase;
}
