<?php

declare(strict_types=1);

namespace App\Tests\Modules\Athena\Infrastructure\Validator;

use App\Modules\Athena\Infrastructure\DataFixtures\Factory\OrbitalBaseFactory;
use App\Modules\Athena\Infrastructure\Validator\CanMakeBuilding;
use App\Modules\Athena\Infrastructure\Validator\DTO\BuildingConstructionOrder;
use App\Modules\Athena\Infrastructure\Validator\HasUnlockedBuilding;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Promethee\Infrastructure\DataFixtures\Factory\TechnologyFactory;
use App\Modules\Promethee\Model\Technology;
use App\Shared\Infrastructure\DataFixtures\Story\SmallMapStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CanMakeBuildingValidatorTest extends KernelTestCase
{
	use Factories;
	use ResetDatabase;

	/**
	 * @dataProvider provideData
	 */
	public function testValidator(OrbitalBase $base, Technology $technology, int $buildingIdentifier, int $targetLevel, ConstraintViolationListInterface $violationList, int $buildingQueuesCount = 0): void
	{
		static::bootKernel();
		/** @var ValidatorInterface $validator */
		$validator = static::getContainer()->get(ValidatorInterface::class);

		$buildingConstructionOrder = new BuildingConstructionOrder(
			orbitalBase: $base,
			technology: $technology,
			buildingIdentifier: $buildingIdentifier,
			targetLevel: $targetLevel,
		);

		$violations = $validator->validate($buildingConstructionOrder, new CanMakeBuilding($buildingQueuesCount));

		static::assertCount(count($violationList), $violations);

		foreach ($violations as $violation) {
			static::assertInstanceOf(HasUnlockedBuilding::class, $violation->getConstraint());
		}
	}

	public function testValidatorManually()
	{
		SmallMapStory::load();

		$base = OrbitalBaseFactory::createOne([
			'levelGenerator' => 9,
			'levelSpatioport' => 0,
		]);
		$technology = TechnologyFactory::createOne();
		$buildingIdentifier = OrbitalBaseResource::SPATIOPORT;
		$targetLevel = 1;
		$violationList = [

		];
		static::bootKernel();
		/** @var ValidatorInterface $validator */
		$validator = static::getContainer()->get(ValidatorInterface::class);

		$buildingConstructionOrder = new BuildingConstructionOrder(
			orbitalBase: $base,
			technology: $technology,
			buildingIdentifier: $buildingIdentifier,
			targetLevel: $targetLevel,
		);

		$violations = $validator->validate($buildingConstructionOrder, new CanMakeBuilding(0));

		static::assertCount(count($violationList), $violations);

		foreach ($violations as $violation) {
			static::assertInstanceOf(HasUnlockedBuilding::class, $violation->getConstraint());
		}
	}

	private function provideData(): \Generator
	{
		yield [
			OrbitalBaseFactory::createOne([
				'levelGenerator' => 9,
				'levelSpatioport' => 0,
			]),
			TechnologyFactory::createOne(),
			OrbitalBaseResource::SPATIOPORT,
			1,
			[

			]
		];
	}
}
