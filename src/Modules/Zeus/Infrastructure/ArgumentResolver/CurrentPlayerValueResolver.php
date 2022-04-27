<?php

namespace App\Modules\Zeus\Infrastructure\ArgumentResolver;

use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class CurrentPlayerValueResolver implements ArgumentValueResolverInterface
{
    public function __construct(protected CurrentPlayerRegistry $currentPlayerRegistry)
    {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if (Player::class !== $argument->getType()) {
            return false;
        }

        return $this->currentPlayerRegistry->has();
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $this->currentPlayerRegistry->get();
    }
}
