<?php
namespace App\Controller;

use App\Entity\User;
use App\Service\GroupingClassesService;
use App\Service\StudentsService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
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
        private StudentsService $studentsService
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

            foreach ($this->studentsService->getListUidStudentFromSirenAndClassName($user->getSirenCourant(), $class) as $uid) {
                $listUidStudent[] = $uid;
            }
            $ticketEnsClasses[] = [
                'className' => $class,
                'listUidStudent' => $listUidStudent,
            ];
        }

        return [
            'groupingClasses' => $groupingClasses,
            'ticketEnsClasses' => $ticketEnsClasses,
        ];
    }
}