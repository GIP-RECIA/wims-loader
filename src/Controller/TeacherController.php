<?php
namespace App\Controller;

use App\Entity\Classes;
use App\Entity\User;
use App\Form\ClassNameType;
use App\Repository\ClassesRepository;
use App\Repository\UserRepository;
use App\Service\ClassesService;
use App\Service\GroupingClassesService;
use App\Service\LdapService;
use App\Service\StudentService;
use App\Service\TeacherService;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
                $class = $this->teacherService->createClass($user, $className);
                $this->addFlash('info', 'La classe "' . $class->getName() . '" a bien été importée');
            } catch (Exception $e) {
                $this->addFlash('alert', 'Erreur lors de la création de la classe');
            }
        } else {
            $this->addFlash('alert', 'Erreur lors de la récupération du nom de la classe');
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

        return [
            'groupingClasses' => $groupingClasses,
            'class' => $classes,
            'diffStudents' => $diffStudents,
        ];
    }

    /**
     * Route affichant les détails d'une classe importée pour pouvoir contrôler
     * les élèves importés dans la classe et déclencher une synchro au besoin.
     */
    #[Route(path:"/enseignant/syncClass/{idClass}", name:"teacherSyncClass")]
    public function syncClass(
        Security $security,
        #[MapEntity(id: 'idClass')] Classes $classes
        ): Response
    {
        $teacher = $this->getUserFromSecurity($security);
        $diffStudents = $this->StudentService->diffStudentFromTeacherAndClass($teacher, $classes);

        try {
            if (sizeof($diffStudents['ldapUnsync']) > 0) {
                $this->teacherService->addStudentsInClass($diffStudents['ldapUnsync'], $classes);
                $this->addFlash('info', 'La synchronisation des élèves a été effectuée correctement');
            } else {
                $this->addFlash('info', 'Aucun nouvel élève a synchroniser');
            }
        } catch (Exception $e) {
            $this->addFlash('alert', 'Erreur lors de la synchronisation des élèves');
        }

        return $this->redirectToRoute('teacherDetailsClass', [
            'idClass' => $classes->getId(),
        ]);
    }
}