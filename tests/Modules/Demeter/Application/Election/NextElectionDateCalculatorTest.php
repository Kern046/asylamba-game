<?php

namespace App\Tests\Modules\Demeter\Application\Election;

use App\Modules\Demeter\Application\Election\NextElectionDateCalculator;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\Election\ElectionFactory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NextElectionDateCalculatorTest extends KernelTestCase
{
	private static NextElectionDateCalculator $nextElectionDateCalculator;

	public static function setUpBeforeClass(): void
	{
		static::bootKernel();

		self::$nextElectionDateCalculator = static::getContainer()->get(NextElectionDateCalculator::class);
	}

	public function testGetNextElectionDate(): void
    {
		self::markTestIncomplete('Not Implemented');
    }

    public function testGetCampaignStartDate(): void
    {
		$faction = FactionFactory::createOne([
			'identifier' => ColorResource::EMPIRE,
			'regime' => Color::REGIME_DEMOCRATIC,
			'electionStatement' => Color::MANDATE,
			'lastElectionHeldAt' => new \DateTimeImmutable('2023-06-01 17:00:00'),
		])->object();
		ElectionFactory::createOne([
			'faction' => $faction,
			'dElection' => new \DateTimeImmutable('2023-06-01 17:00:00'),
		])->object();

		$startDate = self::$nextElectionDateCalculator->getCampaignStartDate($faction);

		self::assertEquals(new \DateTimeImmutable('2023-06-08 17:00:00'), $startDate);
    }

    public function testGetPutschEndDate(): void
    {
		$faction = FactionFactory::createOne([
			'identifier' => ColorResource::EMPIRE,
			'regime' => Color::REGIME_DEMOCRATIC,
			'electionStatement' => Color::MANDATE,
			'lastElectionHeldAt' => new \DateTimeImmutable('2023-06-01 17:00:00'),
		])->object();
		ElectionFactory::createOne([
			'faction' => $faction,
			'dElection' => new \DateTimeImmutable('2023-06-01 17:00:00'),
		])->object();

		$campaignEndDate = self::$nextElectionDateCalculator->getCampaignEndDate($faction);

		self::assertEquals(new \DateTimeImmutable('2023-06-12 17:00:00'), $campaignEndDate);
    }

    public function testGetEndDate(): void
    {
		self::markTestIncomplete('Not Implemented');
    }

    public function testGetStartDate(): void
    {
		self::markTestIncomplete('Not Implemented');
    }

    public function testGetBallotDate(): void
    {
		self::markTestIncomplete('Not Implemented');
    }

    public function testGetSenateUpdateMessage(): void
    {
		self::markTestIncomplete('Not Implemented');
    }

    public function testGetCampaignEndDate(): void
    {
		self::markTestIncomplete('Not Implemented');
    }
}
