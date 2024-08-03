<?php

namespace App\Modules\Promethee\Infrastructure\Validator\DTO;

use App\Modules\Promethee\Model\Technology;

interface HasTechnology
{
	public function getTechnology(): Technology;
}
