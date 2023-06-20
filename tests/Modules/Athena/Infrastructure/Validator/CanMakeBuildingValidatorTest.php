<?php

declare(strict_types=1);

namespace App\Tests\Modules\Athena\Infrastructure\Validator;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CanMakeBuildingValidatorTest extends KernelTestCase
{
	use ResetDatabase;
	use Factories;

	public function testValidator()
	{
		static::bootKernel();
		/** @var ValidatorInterface $validator */
		$validator = static::getContainer()->get(ValidatorInterface::class);



		$validator->validate();
	}
}
