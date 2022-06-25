<?php

namespace App\Modules\Promethee\Manager;

use App\Modules\Promethee\Model\Technology;

class TechnologyManager
{
	public function delete(Technology $technology, int $technologyId): void
	{
		$technology->setTechnology($technologyId, 0);
	}
}
