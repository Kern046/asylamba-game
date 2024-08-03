<?php

namespace App\Modules\Ares\Infrastructure\Validator\DTO;

use App\Modules\Ares\Model\Commander;

interface HasCommander
{
	public function getCommander(): Commander;
}
