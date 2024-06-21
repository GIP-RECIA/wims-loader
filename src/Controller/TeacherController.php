<?php
namespace App\Controller;

use App\Entity\Classes;
use App\Form\ClassNameType;
use App\Repository\ClassesRepository;
use App\Repository\UserRepository;
use App\Service\ClassesService;
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
        private StudentService $StudentService,
        private TeacherService $teacherService,
        private ClassesRepository $classRepo,
        private ClassesService $classesService,
        private UserRepository $userRepo,
        private LdapService $ldapService,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {}

    /**
     * Écran de base pour les enseignants, on y liste les classes déjà importées
     * et les classes disponibles à l'import.
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
        $importedClasses = $this->classRepo->findByGroupingClassesAndTeacher($groupingClasses, $user);
        $importedClassesName = [];
        $formsClassesToImport = [];
        $navigationBar = [['name' => $this->translator->trans('menu.teacherZone')]];

        foreach ($importedClasses as $classes) {
            $importedClassesName[] = $classes->getName();
        }

        foreach ($user->getTicketEnsClasses() as $baseClassName) {
            if (!in_array($this->classesService->generateName($baseClassName, $user), $importedClassesName)) {
                $form = $this->createForm(ClassNameType::class, ['className' => $baseClassName], [
                    'action' => $this->generateUrl('teacherCreateClass', [
                        'step' => 1,
                    ]),
                ]);
                $formsClassesToImport[$baseClassName] = $form->createView();
            }
        }

        krsort($formsClassesToImport);

        return [
            'groupingClasses' => $groupingClasses,
            'navigationBar' => $navigationBar,
            'importedClasses' => $importedClasses,
            'formsClassesToImport' => $formsClassesToImport,
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
        $user = $this->getUserFromSecurity($security);
        $form = $this->createForm(ClassNameType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $className = $form->getData()['className'];
                $siren = $user->getSirenCourant();
                $this->logger->info("Create class '$className' for teacher $user on sire $siren");
                $class = $this->teacherService->createClass($user, $className);
                $this->addFlash('info', $this->translator->trans('teacherZone.message.classImported', ['className' => $class->getName()]));
            } catch (Exception $e) {
                $this->logger->error("Error when creating class for user $user");
                $this->logger->error($e);
                $this->addFlash('alert', $this->translator->trans('teacherZone.message.classCreationError', ['className' => $className]));
            }
        } else {
            $this->logger->warning("Error when retrieving class name for user $user");
            $this->addFlash('alert', $this->translator->trans('teacherZone.message.classGetNameError'));
        }

        return $this->redirectToRoute('teacher');
    }

    /**
     * Route affichant les détails d'une classe importée pour pouvoir contrôler
     * les élèves importés dans la classe et déclencher une synchro au besoin.
     */
    #[Route(path:"/enseignant/detailsClass/{idClass}", name:"teacherDetailsClass")]
    #[Template('web/teacherDetailsClass.html.twig')]
    public function detailsClass(
        Security $security,
        #[MapEntity(id: 'idClass')] Classes $classes
        ): array
    {
        $teacher = $this->getUserFromSecurity($security);
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($teacher->getSirenCourant());
        $diffStudents = $this->StudentService->diffStudentFromTeacherAndClass($teacher, $classes);
        $navigationBar = [
            [
                'name' => $this->translator->trans('menu.teacherZone'),
                'url' => $this->generateUrl('teacher'),
            ], [
                'name' => $this->translator->trans('classDetails.title'),
            ]
        ];

        return [
            'groupingClasses' => $groupingClasses,
            'navigationBar' => $navigationBar,
            'class' => $classes,
            'diffStudents' => $diffStudents,
        ];
    }

    /**
     * Route réalisant une nouvelle synchro sur la classe
     * 
     * @param Classes $classes La classe a synchroniser
     * @return Response La redirection vers la page de détails
     */
    #[Route(path:"/enseignant/syncClass/{idClass}", name:"teacherSyncClass")]
    public function syncClass(
        Security $security,
        #[MapEntity(id: 'idClass')] Classes $classes
        ): Response
    {
        $teacher = $this->getUserFromSecurity($security);
        $this->logger->info("Synchronisation class $classes for teacher $teacher");
        $diffStudents = $this->StudentService->diffStudentFromTeacherAndClass($teacher, $classes);

        try {
            if (sizeof($diffStudents['ldapUnsync']) > 0) {
                $this->teacherService->addStudentsInClass($diffStudents['ldapUnsync'], $classes);
                $this->addFlash('info', $this->translator->trans('teacherZone.message.syncStudentsOk'));
            } else {
                $this->addFlash('info', $this->translator->trans('teacherZone.message.noStudentsToSync'));
            }
        } catch (Exception $e) {
            $this->logger->error("Error when synching class $classes for teacher $teacher");
            $this->logger->error($e);
            $this->addFlash('alert', $this->translator->trans('teacherZone.message.syncStudentsNok'));
        }

        return $this->redirectToRoute('teacherDetailsClass', [
            'idClass' => $classes->getId(),
        ]);
    }
}