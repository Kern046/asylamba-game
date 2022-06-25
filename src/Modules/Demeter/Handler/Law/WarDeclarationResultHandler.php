<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Message\Law\WarDeclarationResultMessage;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class WarDeclarationResultHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private CommercialRouteManager   $commercialRouteManager,
		private LawRepositoryInterface   $lawRepository,
	) {
	}

	public function __invoke(WarDeclarationResultMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId());
		$color = $law->faction;
		$enemyColor = $this->colorRepository->get($law->options['rColor']);

		$color->colorLink[$law->options['rColor']] = Color::ENEMY;
		$enemyColor->colorLink[$color->id->toRfc4122()] = Color::ENEMY;
		$law->statement = Law::OBSOLETE;
		$this->commercialRouteManager->freezeRoute($color, $enemyColor);
		$this->colorRepository->save($color);
		$this->lawRepository->save($law);
	}
}
