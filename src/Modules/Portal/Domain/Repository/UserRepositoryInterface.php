<?php

declare(strict_types=1);

namespace App\Modules\Portal\Domain\Repository;

use App\Modules\Portal\Domain\Entity\User;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends EntityRepositoryInterface<User>
 */
interface UserRepositoryInterface extends EntityRepositoryInterface, PasswordUpgraderInterface
{
}
