<?php

namespace App\Modules\Athena\Infrastructure\Validator\DTO;

interface HasBuildingIdentifier
{
	public function getBuildingIdentifier(): int;
}
