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

use App\Repository\CohortRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractWimsLoaderController
{
    public function __construct(
        private CohortRepository $cohortRepo,
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
        ];
    }
}