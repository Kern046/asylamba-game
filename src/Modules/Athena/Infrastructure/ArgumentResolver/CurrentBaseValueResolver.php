<?php

namespace App\Modules\Athena\Infrastructure\ArgumentResolver;

use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Model\OrbitalBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class CurrentBaseValueResolver implements ValueResolverInterface
{
	public function __construct(private CurrentPlayerBasesRegistry $currentPlayerBasesRegistry)
	{
	}

	public function resolve(Request $request, ArgumentMetadata $argument): array
	{
		if (OrbitalBase::class !== $argument->getType()) {
			return [];
		}
		if (null === $this->currentPlayerBasesRegistry->current()) {
			return [];
		}

		return [$this->currentPlayerBasesRegistry->current()];
	}
}
