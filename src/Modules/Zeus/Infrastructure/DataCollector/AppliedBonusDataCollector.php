<?php

namespace App\Modules\Zeus\Infrastructure\DataCollector;

use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppliedBonusDataCollector extends AbstractDataCollector
{
	public function __construct(private BonusApplierInterface $bonusApplier)
	{
	}

	public function collect(Request $request, Response $response, \Throwable $exception = null)
	{
		// TODO: Implement collect() method.
	}
}
