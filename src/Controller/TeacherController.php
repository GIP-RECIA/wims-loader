<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\ClassNameType;
use App\Repository\ClassesRepository;
use App\Service\ClassesService;
use App\Service\GroupingClassesService;
use App\Service\StudentService;
use App\Service\TeacherService;
use Exception;
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
    ) {}

    #[Route(path:"/enseignant/", name:"teacher")]
    #[Template('web/teacher.html.twig')]
    public function indexTeacher(Security $security): array
    {
        $user = $this->getUserFromSecurity($security);
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($user->getSirenCourant());
        $importedClasses = $this->classRepo->findByGroupingClassesAndTeacher($groupingClasses, $user);
        $importedClassesName = [];
        $classesToImport = [];
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

        ksort($classesToImport);

        return [
            'groupingClasses' => $groupingClasses,
            'importedClasses' => $importedClasses,
            'formsClassesToImport' => $formsClassesToImport,
        ];
    }

    #[Route(path:"/enseignant/createClass/{step}", name:"teacherCreateClass", requirements: ['step' => '1|2'])]
    public function createClass(Request $request, Security $security, int $step): Response
    {
        $user = $this->getUserFromSecurity($security);

        if ($step === 1) {
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
        
        return $this->redirectToRoute('teacher');
    }
}