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
use App\Enum\CohortType;
use App\Form\NameType;
use App\Repository\CohortRepository;
use App\Repository\UserRepository;
use App\Service\CohortNameService;
use App\Service\CohortService;
use App\Service\GroupingClassesService;
use App\Service\LdapService;
use App\Service\StudentService;
use App\Service\TeacherService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ENS')]
class TeacherController extends AbstractWimsLoaderController
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private GroupingClassesService $groupingClassesService,
        private StudentService $studentService,
        private TeacherService $teacherService,
        private CohortRepository $cohortRepo,
        private CohortNameService $cohortNameService,
        private CohortService $cohortService,
        private UserRepository $userRepo,
        private LdapService $ldapService,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {}

    /**
     * Écran de base pour les enseignants, on y liste les classes déjà importées,
     * les classes disponibles à l'import et pareille pour les groupes
     * pédagogique.
     *
     * @param Security $security
     * @return array
     */
    #[Route(path:"/enseignant/", name:"teacher")]
    #[Template('web/teacher.html.twig')]
    public function indexTeacher(Security $security): array
    {
        $user = $this->getUserFromSecurity($security);
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($user->getSirenCourant());
        $importedClasses = $this->cohortRepo->findByGroupingClassesAndTeacher($groupingClasses, $user, CohortType::TYPE_CLASS);
        $importedGroups = $this->cohortRepo->findByGroupingClassesAndTeacher($groupingClasses, $user, CohortType::TYPE_GROUP);
        $importedClassesName = [];
        $importedGroupsName = [];
        $formsClassesToImport = [];
        $formsGroupsToImport = [];
        $navigationBar = [['name' => $this->translator->trans('menu.teacherZone')]];
        $cohorts = $this->teacherService->getCohortsOfTeacher($user);

        foreach ($importedClasses as $classes) {
            $importedClassesName[] = $classes->getName();
        }

        foreach ($cohorts['classes'] as $baseClassName) {
            if (!in_array($this->cohortNameService->generateName($baseClassName, $user), $importedClassesName)) {
                $form = $this->createForm(NameType::class, [
                    'name' => $baseClassName], [
                    'action' => $this->generateUrl('teacherCreateClass'),
                ]);
                $formsClassesToImport[$baseClassName] = $form->createView();
            }
        }

        foreach ($importedGroups as $groups) {
            $importedGroupsName[] = $groups->getName();
        }

        foreach ($cohorts['groups'] as $baseGroupName) {
            if (!in_array($this->cohortNameService->generateName($baseGroupName, $user), $importedGroupsName)) {
                $form = $this->createForm(NameType::class, [
                    'name' => $baseGroupName], [
                    'action' => $this->generateUrl('teacherCreateGroup'),
                ]);
                $formsGroupsToImport[$baseGroupName] = $form->createView();
            }
        }

        krsort($formsClassesToImport);
        krsort($formsGroupsToImport);

        return [
            'groupingClasses' => $groupingClasses,
            'navigationBar' => $navigationBar,
            'importedClasses' => $importedClasses,
            'formsClassesToImport' => $formsClassesToImport,
            'importedGroups' => $importedGroups,
            'formsGroupsToImport' => $formsGroupsToImport,
        ];
    }

    /**
     * Route permettant de réaliser l'import d'une classe. Cette route n'affiche
     * rien car elle fait directement une redirection vers la page de l'enseignant
     *
     * @param Request $request
     * @param Security $security
     * @return Response
     */
    #[Route(path:"/enseignant/createClass", name:"teacherCreateClass")]
    public function createClass(Request $request, Security $security): Response
    {
        return $this->createCohort($request, $security, CohortType::TYPE_CLASS);
    }

    /**
     * Route permettant de réaliser l'import d'un groupe. Cette route n'affiche
     * rien car elle fait directement une redirection vers la page de l'enseignant
     *
     * @param Request $request
     * @param Security $security
     * @return Response
     */
    #[Route(path:"/enseignant/createGroup", name:"teacherCreateGroup")]
    public function createGroup(Request $request, Security $security): Response
    {
        return $this->createCohort($request, $security, CohortType::TYPE_GROUP);
    }

    /**
     * Route affichant les détails d'une cohorte importée pour pouvoir contrôler
     * les élèves importés dans la cohorte et déclencher une synchro au besoin.
     */
    #[Route(path:"/enseignant/detailsCohort/{idCohort}", name:"teacherDetailsCohort")]
    #[Template('web/teacherDetailsCohort.html.twig')]
    public function detailsCohort(
        Security $security,
        #[MapEntity(id: 'idCohort')] Cohort $cohort
        ): array
    {
        $res = $this->cohortService->detailsCohort($cohort);
        $res['navigationBar'] = [
            [
                'name' => $this->translator->trans('menu.teacherZone'),
                'url' => $this->generateUrl('teacher'),
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
    #[Route(path:"/enseignant/syncCohort/{idCohort}", name:"teacherSyncCohort")]
    public function syncClass(
        Security $security,
        #[MapEntity(id: 'idCohort')] Cohort $cohort
        ): Response
    {
        $teacher = $this->getUserFromSecurity($security);
        $this->logger->info("Synchronisation cohort $cohort for teacher $teacher");
        $diffStudents = $this->studentService->diffStudentFromTeacherAndCohort($cohort);

        try {
            if (sizeof($diffStudents['ldapUnsync']) > 0) {
                $this->teacherService->addStudentsInCohort($diffStudents['ldapUnsync'], $cohort);
                $this->addFlash('info', $this->translator->trans('teacherZone.message.syncStudentsOk'));
            } else {
                $this->addFlash('info', $this->translator->trans('teacherZone.message.noStudentsToSync'));
            }
        } catch (Exception $e) {
            $this->logger->error("Error when synching cohort $cohort for teacher $teacher");
            $this->logger->error($e);
            $this->addFlash('alert', $this->translator->trans('teacherZone.message.syncStudentsNok'));
        }

        return $this->redirectToRoute('teacherDetailsCohort', [
            'idCohort' => $cohort->getId(),
        ]);
    }

    // FIXME: a finir
    /**
     * Route permettant de forcer une synchro complète sur la cohorte (effacement et recréation des fichiers)
     * 
     * @param Cohort $cohort La cohorte a synchroniser
     * @return Response La redirection vers la page de détails
     */
    #[Route(path:"/enseignant/forceFullSyncStudentsCohort/{idCohort}", name:"teacherForceFullSyncStudentsCohort")]
    public function forceFullSyncStudents(
        Security $security,
        #[MapEntity(id: 'idCohort')] Cohort $cohort
        ): Response
    {
        // TODO: ajouter le flash
        $this->addFlash('info', $this->translator->trans('home.title'));
        return $this->redirectToRoute('teacherDetailsCohort', [
            'idCohort' => $cohort->getId(),
        ]);
    }

    /**
     * Route permettant de réaliser l'import d'une cohorte. Cette route n'affiche
     * rien car elle fait directement une redirection vers la page de l'enseignant
     *
     * @param Request $request
     * @param Security $security
     * @return Response
     */
    private function createCohort(Request $request, Security $security, CohortType $type): Response
    {
        $user = $this->getUserFromSecurity($security);
        $form = $this->createForm(NameType::class);
        $form->handleRequest($request);
        $key = Cohort::cohortTypeString($type);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $cohortName = $form->getData()['name'];
                $siren = $user->getSirenCourant();
                $this->logger->info("Create $key '$cohortName' for teacher $user on sire $siren");
                $cohort = $this->teacherService->createCohort($user, $cohortName, $type);
                $this->addFlash('info', $this->translator->trans("teacherZone.message.$key.imported", ['%name%' => $cohort->getName()]));
            } catch (Exception $e) {
                $this->logger->error("Error when creating $key for user $user");
                $this->logger->error($e);
                $this->addFlash('alert', $this->translator->trans("teacherZone.message.$key.creationError", ['%name%' => $cohortName]));
            }
        } else {
            $this->logger->warning("Error when retrieving $key name for user $user");
            $this->addFlash('alert', $this->translator->trans("teacherZone.message.$key.getNameError"));
        }

        return $this->redirectToRoute('teacher');
    }
}