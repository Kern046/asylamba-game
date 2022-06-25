<?php

namespace App\Modules\Gaia\Infrastructure\Validator\DTO;

use App\Modules\Gaia\Model\Place;

interface HasPlace
{
	public function getPlace(): Place;
}
