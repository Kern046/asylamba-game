<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Repository\Doctrine;

use App\Modules\Portal\Domain\Entity\User;
use App\Modules\Portal\Domain\Repository\UserRepositoryInterface;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends DoctrineRepository<User>
 */
class UserRepository extends DoctrineRepository implements UserRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, User::class);
	}

	public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
	{
		$user->setPassword($newHashedPassword);
		$this->save($user);
	}
}
