<?php

namespace App\Modules\Athena\Infrastructure\Controller\Financial;

use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateBaseInvestments extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		OrbitalBase $currentBase,
		string $category,
	): Response {
		$credit = $request->request->getInt('credit');

		match ($category) {
			'school' => $this->updateSchoolInvestment($currentBase, $credit),
			'antispy' => $this->updateAntiSpyInvestment($currentBase, $credit),
			default => throw new BadRequestHttpException('Invalid category'),
		};

		$orbitalBaseRepository->save($currentBase);

		return $this->redirectToRoute('financial_investments');
	}

	protected function updateSchoolInvestment(OrbitalBase $base, int $credit): void
	{
		if (50000 < $credit) {
			throw new BadRequestHttpException('La limite maximale d\'investissement dans l\'école de commandement est de 50\'000 crédits.');
		}
		$base->iSchool = $credit;
		$this->addFlash('success', 'L\'investissement dans l\'école de commandement de votre base '.$base->name.' a été modifié');
	}

	protected function updateAntiSpyInvestment(OrbitalBase $base, int $credit): void
	{
		if (100000 < $credit) {
			throw new BadRequestHttpException('La limite maximale d\'investissement dans l\'anti-espionnage est de 100\'000 crédits.');
		}
		$base->iAntiSpy = $credit;
		$this->addFlash('success', 'L\'investissement dans l\'anti-espionnage sur votre base '.$base->name.' a été modifié');
	}
}
