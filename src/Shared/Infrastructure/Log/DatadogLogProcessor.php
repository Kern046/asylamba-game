<?php

namespace App\Shared\Infrastructure\Log;

use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;

class DatadogLogProcessor
{
	private string $requestId = 'None';
	private array $serverData = [];
	private array $requestParams = [];
	private array $queryParams = [];

	private const array PRIVATE_PARAMS = [
		'password',
		'csrf_token',
	];

	public function __construct(private readonly RequestStack $requestStack)
	{
		if (null !== $request = $this->requestStack->getMainRequest()) {
			$this->requestId = substr(uniqid(), -8);
			$this->requestParams = $this->cleanInput($request->request->all());
			$this->queryParams = $this->cleanInput($request->query->all());
		}
		$this->serverData = [
			'http.url' => (@$_SERVER['HTTP_HOST']).'/'.(@$_SERVER['REQUEST_URI']),
			'http.method' => @$_SERVER['REQUEST_METHOD'],
			'http.useragent' => @$_SERVER['HTTP_USER_AGENT'],
			'http.referer' => @$_SERVER['HTTP_REFERER'],
			'http.x_forwarded_for' => @$_SERVER['HTTP_X_FORWARDED_FOR'],
		];
	}

	public function __invoke(LogRecord $record): LogRecord
	{
		$record->extra['http.request_id'] = $this->requestId;
		$record->extra['http.session_id'] = $this->requestStack->getMainRequest()?->hasPreviousSession()
			? $this->requestStack->getMainRequest()->getSession()->getId()
			: 'Unknown';
		$record->extra['http.url'] = $this->serverData['http.url'];
		$record->extra['http.method'] = $this->serverData['http.method'];
		$record->extra['http.useragent'] = $this->serverData['http.useragent'];
		$record->extra['http.referer'] = $this->serverData['http.referer'];
		$record->extra['http.x_forwarded_for'] = $this->serverData['http.x_forwarded_for'];
		$record->extra['http.query_params'] = $this->queryParams;
		$record->extra['http.request_params'] = $this->requestParams;

		return $record;
	}

	private function cleanInput($array): array
	{
		$toReturn = [];
		foreach (array_keys($array) as $key) {
			$hasPrivateParam = false;
			foreach (self::PRIVATE_PARAMS as $privateParam) {
				$hasPrivateParam |= str_contains($key, $privateParam);
			}
			if (!$hasPrivateParam) {
				$toReturn[$key] = $array[$key];
			}
		}

		return $toReturn;
	}
}
