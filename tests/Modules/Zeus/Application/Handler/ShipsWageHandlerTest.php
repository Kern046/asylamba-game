<?php

namespace App\Tests\Modules\Zeus\Application\Handler;

use App\Modules\Zeus\Application\Handler\ShipsWageHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ShipsWageHandlerTest extends KernelTestCase
{
	public function test(): void
	{
		static::bootKernel();

		$shipsWageHandler = static::getContainer()->get(ShipsWageHandler::class);

		static::markTestIncomplete('Not implemented');
	}
}
