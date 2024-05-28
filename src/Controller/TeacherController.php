<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\ClassesRepository;
use App\Service\ClassesService;
use App\Service\GroupingClassesService;
use App\Service\StudentService;
use App\Service\TeacherService;
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

        foreach ($importedClasses as $classes) {
            $importedClassesName[] = $classes->getName();
        }

        foreach ($user->getTicketEnsClasses() as $baseClassName) {
            if (!in_array($this->classesService->generateName($baseClassName, $user), $importedClassesName)) {
                $classesToImport[] = $baseClassName;
            }
        }

        sort($classesToImport);

        return [
            'groupingClasses' => $groupingClasses,
            'classesToImport' => $classesToImport,
            'importedClasses' => $importedClasses,
        ];
    }

    #[Route(path:"/enseignant/createClass/{className}", name:"teacherCreateClass")]
    public function createClass(Request $request, Security $security): Response
    {
        $user = $this->getUserFromSecurity($security);
        $className = $request->attributes->get('className');
        $class = $this->teacherService->createClass($user, $className);
        $this->addFlash('info', 'La classe "' . $class->getName() . '" a bien été importée');
        return $this->redirectToRoute('teacher');
    }
}