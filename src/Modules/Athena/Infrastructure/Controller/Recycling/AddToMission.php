<?php

namespace App\Modules\Athena\Infrastructure\Controller\Recycling;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\RecyclingMissionManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AddToMission extends AbstractController
{
    public function __invoke(
        Request $request,
        OrbitalBase $currentBase,
        OrbitalBaseManager $orbitalBaseManager,
        OrbitalBaseHelper $orbitalBaseHelper,
        RecyclingMissionManager $recyclingMissionManager,
        EntityManager $entityManager,
        int $id,
    ): Response {
        $quantity = $request->request->get('quantity');

        if (false !== $quantity) {
            if ($quantity > 0) {
                $maxRecyclers = $orbitalBaseHelper->getInfo(OrbitalBaseResource::RECYCLING, 'level', $currentBase->levelRecycling, 'nbRecyclers');
                $usedRecyclers = 0;

                $baseMissions = $recyclingMissionManager->getBaseActiveMissions($currentBase->getId());

                foreach ($baseMissions as $mission) {
                    $usedRecyclers += $mission->recyclerQuantity + $mission->addToNextMission;
                }

                if ($maxRecyclers - $usedRecyclers >= $quantity) {
                    $mission = null;
                    foreach ($baseMissions as $baseMission) {
                        if ($baseMission->id === $id && $baseMission->isActive()) {
                            $mission = $baseMission;
                            break;
                        }
                    }
                    if (null !== $mission) {
                        $mission->addToNextMission += $quantity;

                        $entityManager->flush($mission);

                        $this->addFlash('success', 'Vos recycleurs ont bien été affectés, ils seront ajoutés à la prochaine mission.');

                        return $this->redirect($request->headers->get('referer'));
                    } else {
                        throw new ErrorException('Il y a un problème, la mission est introuvable. Veuillez contacter un administrateur.');
                    }
                } else {
                    throw new ErrorException('Vous n\'avez pas assez de recycleurs libres pour lancer cette mission.');
                }
            } else {
                throw new FormException('Ca va être dur de recycler avec autant peu de recycleurs. Entrez un nombre plus grand que zéro.');
            }
        } else {
            throw new FormException('pas assez d\'informations pour créer une mission de recyclage');
        }
    }
}
