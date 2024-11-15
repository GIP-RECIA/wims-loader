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

use App\Service\GroupingClassesService;
use App\Service\LdapService;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RootController extends AbstractWimsLoaderController
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private LdapService $ldapService,
        private GroupingClassesService $groupingClassesService,
        private TranslatorInterface $translator,
    ) {}

    #[Route(path:"/home", name:"home")]
    public function home(Security $security): Response
    {
        $user = $this->getUserFromSecurity($security);
        $isTeacher = $this->authorizationChecker->isGranted('ROLE_ENS');
        $isStudent = $this->authorizationChecker->isGranted('ROLE_ELV');
        $isAdmin = $this->authorizationChecker->isGranted('ROLE_ADMIN');
        $groupingClasses = null;

        if ($isTeacher && !$isStudent) {
            return $this->redirectToRoute('teacher');
        } else if ($isStudent) {
            return $this->redirectToRoute('student');
        } else if ($isAdmin) {
            return $this->redirectToRoute('adminCohorts');
        }

        try {
            $groupingClasses = $this->groupingClassesService->loadGroupingClasses($user->getSirenCourant());
        } catch (Exception $e) {}
        
        $navigationBar = [['name' => $this->translator->trans('menu.home')]];

        return $this->render('web/home.html.twig', [
            'groupingClasses' => $groupingClasses,
            'navigationBar' => $navigationBar,
        ]);
    }

    /**
     * Permet de tester la bonne gestion des logs d'erreur
     *
     * @return Response
     */
    #[Route(path:"/error", name:"error")]
    public function error(): Response
    {
        throw new Exception("Erreur de test");
    }

    /**
     * Permet de se déconnecter et de retourner au portail
     *
     * @param Security $security
     * @return Response
     */
    #[Route('/logout', name:'logout')]
    public function logout(Security $security): Response
    {
        $security->logout(false);

        return $this->redirect('/');
    }
}