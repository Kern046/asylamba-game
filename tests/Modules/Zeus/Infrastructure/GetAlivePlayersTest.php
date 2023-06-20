<?php

declare(strict_types=1);

namespace App\Tests\Modules\Zeus\Infrastructure;

use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use App\Modules\Zeus\Infrastructure\Validator\IsAlive;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class GetAlivePlayersTest extends KernelTestCase
{
	use Factories;
	use ResetDatabase;

	public function testGetPlayers(): void
	{
		static::bootKernel();
		/** @var PlayerRepositoryInterface $playerRepository */
		$playerRepository = static::getContainer()->get(PlayerRepositoryInterface::class);

		PlayerFactory::createMany(5, ['statement' => Player::ACTIVE]);
		PlayerFactory::createMany(3, ['statement' => Player::DEAD]);

		$alivePlayers = $playerRepository->getBySpecification(new IsAlive());

		static::assertCount(5, $alivePlayers);

		foreach ($alivePlayers as $player) {
			static::assertNotSame(Player::DEAD, $player->statement);
		}
	}
}
