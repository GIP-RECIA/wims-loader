<?php
/**
 * Copyright © 2024 GIP-RECIA (https://www.recia.fr/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace App\Controller;

use App\Entity\Cohort;
use App\Repository\CohortRepository;
use App\Service\CohortService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractWimsLoaderController
{
    public function __construct(
        private CohortRepository $cohortRepo,
        private CohortService $cohortService,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Route permettant d'afficher toutes les cohortes
     *
     * @param Security $security
     * @return array
     */
    #[Route(path:"/admin/cohorts", name:"adminCohorts")]
    #[Template('web/admin.html.twig')]
    public function indexAdmin(Security $security): array
    {
        $data = $this->cohortRepo->findAllData();
        return [
            'data' => $data,
            'navigationBar' => [[
                'name' => $this->translator->trans('menu.adminZone'),
            ]]
        ];
    }

    /**
     * Route affichant les détails d'une cohorte importée pour pouvoir contrôler
     * les élèves importés dans la cohorte et déclencher une synchro au besoin.
     */
    #[Route(path:"/admin/detailsCohort/{idCohort}", name:"adminDetailsCohort")]
    #[Template('web/adminDetailsCohort.html.twig')]
    public function detailsCohort(
        Security $security,
        #[MapEntity(id: 'idCohort')] Cohort $cohort
        ): array
    {
        $res = $this->cohortService->detailsCohort($cohort);
        $res['navigationBar'] = [
            [
                'name' => $this->translator->trans('menu.adminZone'),
                'url' => $this->generateUrl('adminCohorts'),
            ], [
                'name' => $this->translator->trans('cohortDetails.title'),
            ]
        ];
        
        return $res;
    }

    /**
     * Route réalisant une nouvelle synchro sur la cohorte
     * 
     * @param Cohort $cohort La cohorte a synchroniser
     * @return Response La redirection vers la page de détails
     */
    #[Route(path:"/admin/syncCohort/{idCohort}", name:"adminSyncCohort")]
    public function syncClass(
        Security $security,
        #[MapEntity(id: 'idCohort')] Cohort $cohort
        ): Response
    {
        $user = $this->getUserFromSecurity($security);
        $message = $this->cohortService->syncCohort($cohort, $user);

        if (null !== $message) {
            $this->addFlash($message['type'], $message['msg']);
        }
        
        return $this->redirectToRoute('adminDetailsCohort', [
            'idCohort' => $cohort->getId(),
        ]);
    }
}