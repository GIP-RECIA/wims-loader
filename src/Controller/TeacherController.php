<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\ClassesRepository;
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
    ) {}

    #[Route(path:"/enseignant/", name:"teacher")]
    #[Template('web/teacher.html.twig')]
    public function indexTeacher(Security $security): array
    {
        $user = $this->getUserFromSecurity($security);
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($user->getSirenCourant());
        $ticketEnsClasses = [];

        foreach ($user->getTicketEnsClasses() as $class) {
            $listUidStudent = [];

            foreach ($this->StudentService->getListUidStudentFromSirenAndClassName($user->getSirenCourant(), $class) as $uid) {
                $listUidStudent[] = $uid;
            }
            $ticketEnsClasses[] = [
                'className' => $class,
                'listUidStudent' => $listUidStudent,
            ];
        }

        $classes = $this->classRepo->findByGroupingClassesAndTeacher($groupingClasses, $user);

        return [
            'groupingClasses' => $groupingClasses,
            'ticketEnsClasses' => $ticketEnsClasses,
            'classes' => $classes,
        ];
    }

    #[Route(path:"/enseignant/createClass/{className}", name:"teacherCreateClass")]
    public function createClass(Request $request, Security $security): Response
    {
        $user = $this->getUserFromSecurity($security);
        $className = $request->attributes->get('className');
        $class = $this->teacherService->createClass($user, $className);
        return new Response("<html><body>".$class->getName()."</body></html>");
    }
}