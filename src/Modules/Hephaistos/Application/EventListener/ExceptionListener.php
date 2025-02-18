<?php

declare(strict_types=1);

namespace App\Modules\Hephaistos\Application\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsEventListener]
readonly class ExceptionListener
{
	public function __invoke(ExceptionEvent $event): void
	{
		$exception = $event->getThrowable();

		if (!$exception instanceof HttpExceptionInterface) {
			return;
		}

		$request = $event->getRequest();
		if (null === ($referer = $request->headers->get('referer'))) {
			return;
		}

		if (!str_contains($request->headers->get('accept'), 'text/html')) {
			return;
		}

		$flashCode = match ($exception->getStatusCode()) {
			Response::HTTP_CONFLICT => '103',
			default => 'error',
		};

		$request->getSession()->getFlashBag()->add($flashCode, $exception->getMessage());

		$event->setResponse(new RedirectResponse($referer));
	}
}
