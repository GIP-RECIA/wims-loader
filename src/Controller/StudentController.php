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

use App\Enum\CohortType;
use App\Repository\CohortRepository;
use App\Service\GroupingClassesService;
use App\Service\StudentService;
use App\Service\WimsUrlGeneratorService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ELV')]
class StudentController extends AbstractWimsLoaderController
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private GroupingClassesService $groupingClassesService,
        private StudentService $StudentService,
        private CohortRepository $cohortRepo,
        private WimsUrlGeneratorService $wimsUrlGeneratorService,
        private TranslatorInterface $translator,
    ) {}

    #[Route(path:"/eleve/", name:"student")]
    public function indexStudent(Security $security): Response
    {
        $user = $this->getUserFromSecurity($security);
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($user->getSirenCourant());
        $srcCohorts = $this->cohortRepo->findByGroupingClassesAndStudent($groupingClasses, $user);
        $autoRedirectStudent = $this->getParameter('app.autoRedirectStudent');
        $cohorts = ['classes' => [], 'groups' => []];
        $navigationBar = [['name' => $this->translator->trans('menu.studentZone')]];

        if ($autoRedirectStudent && count($srcCohorts) === 1) {
            return $this->redirect($this->wimsUrlGeneratorService->wimsUrlClassForStudent($srcCohorts[0]));
        }

        foreach ($srcCohorts as $cohort) {
            if ($cohort->getType() === CohortType::TYPE_CLASS) {
                $cohorts['classes'][] = $cohort;
            } else {
                $cohorts['groups'][] = $cohort;
            }
        }


        return $this->render('web/student.html.twig', [
            'groupingClasses' => $groupingClasses,
            'navigationBat' => $navigationBar,
            'cohorts' => $cohorts,
        ]);
    }
}