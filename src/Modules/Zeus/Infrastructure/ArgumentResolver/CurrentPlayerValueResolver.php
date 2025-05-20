<?php

namespace App\Modules\Zeus\Infrastructure\ArgumentResolver;

use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class CurrentPlayerValueResolver implements ValueResolverInterface
{
	public function __construct(private CurrentPlayerRegistry $currentPlayerRegistry)
	{
	}

	public function resolve(Request $request, ArgumentMetadata $argument): array
	{
		if (Player::class !== $argument->getType()) {
			return [];
		}

		if (!$this->currentPlayerRegistry->has()) {
			return [];
		}

		return [$this->currentPlayerRegistry->get()];
	}
}
