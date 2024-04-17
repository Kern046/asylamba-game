<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base;

use App\Modules\Gaia\Domain\Repository\PlaceRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AutocompleteBase extends AbstractController
{
	public function __invoke(
		Request $request,
		PlaceRepositoryInterface $placeRepository,
	): Response {
		if (null === ($search = $request->query->get('q'))) {
			throw new BadRequestHttpException('Missing search parameter');
		}

		dump($placeRepository->search($search));

		return $this->render('blocks/athena/autocomplete_base.html.twig', [
			'places' => $placeRepository->search($search),
		]);
	}
}
