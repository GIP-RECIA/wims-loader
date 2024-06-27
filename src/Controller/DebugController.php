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
use App\Repository\GroupingClassesRepository;
use App\Repository\UserRepository;
use App\Service\LdapService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
#[IsGranted('IS_DEV_ENV')]
class DebugController extends AbstractWimsLoaderController
{
    public function __construct(
        private LdapService $ldapService,
        private UserRepository $userRepo,
        private GroupingClassesRepository $groupingClassesRepo,
        private CohortRepository $cohortRepo,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Affichage des headers
     */
    #[Route(path:"/debug/infos", name:"debug_user")]
    #[Template('web/debug.html.twig')]
    public function infos(Security $security): array
    {
        $user = $this->getUserFromSecurity($security);
        $userLdap = $this->ldapService->findOneUserByUid($user->getUid());
        $userBdd = $this->userRepo->findOneByUid($user->getUid());
        $groupingClasses = $this->groupingClassesRepo->findOneBySiren($user->getSirenCourant());
        $navigationBar = [['name' => $this->translator->trans('menu.debug')]];
        $dumpArray = [
            $this->translator->trans('debug.categoryTitle.user') => $user,
            $this->translator->trans('debug.categoryTitle.userDataLdap') => $userLdap,
            $this->translator->trans('debug.categoryTitle.userDataBdd') => $userBdd,
        ];

        if ($groupingClasses !== null) {
            $dumpArray[$this->translator->trans('debug.categoryTitle.groupingClassesDataBdd')] = $groupingClasses;
            $classesStudentBdd = $this->cohortRepo->findByGroupingClassesAndStudent($groupingClasses, $user);
            $dumpArray[$this->translator->trans('debug.categoryTitle.cohortsDataBddForStudent')] = $classesStudentBdd;
            $classesTeacherBdd = $this->cohortRepo->findByGroupingClassesAndTeacher($groupingClasses, $user);
            $dumpArray[$this->translator->trans('debug.categoryTitle.cohortsDataBddForTeacher')] = $classesTeacherBdd;
        }
        
        return [
            'navigationBar' => $navigationBar,
            'dumpArray' => $dumpArray,
        ];
    }

    /**
     * Affichage du phpinfo
     */
    #[Route(path:"/debug/phpinfo", name:"debug_phpinfo")]
    #[Template('web/debug.html.twig')]
    public function phpinfo(): array
    {
        $navigationBar = [['name' => $this->translator->trans('menu.debug')]];
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents ();
        ob_end_clean();

        return [
            'navigationBar' => $navigationBar,
            'content' => $phpinfo,
        ];
    }

    /**
     * Affichage des headers
     */
    #[Route(path:"/debug/headers", name:"debug_headers")]
    #[Template('web/debug.html.twig')]
    public function headers(): array
    {
        $navigationBar = [['name' => $this->translator->trans('menu.debug')]];

        return [
            'navigationBar' => $navigationBar,
            'dumpArray' => [
                'headers' => $_SERVER,
            ]
        ];
    }
}