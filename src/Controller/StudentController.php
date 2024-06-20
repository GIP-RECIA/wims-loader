<?php
namespace App\Controller;

use App\Repository\ClassesRepository;
use App\Service\GroupingClassesService;
use App\Service\StudentService;
use App\Service\WimsUrlGeneratorService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ELV')]
class StudentController extends AbstractWimsLoaderController
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private GroupingClassesService $groupingClassesService,
        private StudentService $StudentService,
        private ClassesRepository $classRepo,
        private WimsUrlGeneratorService $wimsUrlGeneratorService,
        private TranslatorInterface $translator,
    ) {}

    #[Route(path:"/eleve/", name:"student")]
    public function indexStudent(Security $security): Response
    {
        $user = $this->getUserFromSecurity($security);
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($user->getSirenCourant());
        $classes = $this->classRepo->findByGroupingClassesAndStudent($groupingClasses, $user);
        $autoRedirectStudent = $this->getParameter('app.autoRedirectStudent');
        $navigationBar = [['name' => $this->translator->trans('menu.studentZone')]];

        if ($autoRedirectStudent && count($classes) === 1) {
            return $this->redirect($this->wimsUrlGeneratorService->wimsUrlClassForStudent($classes[0]));
        }

        return $this->render('web/student.html.twig', [
            'groupingClasses' => $groupingClasses,
            'navigationBat' => $navigationBar,
            'classes' => $classes,
        ]);
    }
}