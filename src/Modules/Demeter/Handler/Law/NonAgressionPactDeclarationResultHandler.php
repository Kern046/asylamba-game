<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Message\Law\NonAgressionPactDeclarationResultMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class NonAgressionPactDeclarationResultHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private CommercialRouteManager $commercialRouteManager,
		private LawRepositoryInterface $lawRepository,
	) {
	}

	public function __invoke(NonAgressionPactDeclarationResultMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId());
		$color = $law->faction;
		$enemyColor = $this->colorRepository->get($law->options['rColor']);

		$color->colorLink[$law->options['rColor']] = Color::NEUTRAL;
		$law->statement = Law::OBSOLETE;
		$this->commercialRouteManager->freezeRoute($color, $enemyColor);
		$this->colorRepository->save($color);
		$this->lawRepository->save($law);
	}
}
