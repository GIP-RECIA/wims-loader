<?php
namespace App\Controller;

use App\Entity\User;
use App\Service\GroupingClassesService;
use App\Service\StudentService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ELV')]
class StudentController extends AbstractWimsLoaderController
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private GroupingClassesService $groupingClassesService,
        private StudentService $StudentService
    ) {}

    #[Route(path:"/eleve/", name:"student")]
    #[Template('web/student.html.twig')]
    public function indexStudent(Security $security): array
    {
        $user = $this->getUserFromSecurity($security);
        $classes = $this->StudentService->getListClassNameFromSirenAndUidStudent($user);
        return [
            'classes' => $classes,
        ];
    }
}