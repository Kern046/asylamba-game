<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Ui\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewMapSandbox extends AbstractController
{
    public function __invoke(): Response
	{
		return $this->render('pages/gaia/map_sandbox.html.twig');
	}
}
