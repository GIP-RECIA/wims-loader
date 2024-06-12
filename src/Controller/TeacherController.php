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
use Symfony\Component\Ldap\Entry;
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

    #[Route(path:"/enseignant/detailsClass/{idClass}", name:"teacherDetailsClass")]
    #[Template('web/teacherDetailsClass.html.twig')]
    public function detailsClass(
        Request $request,
        Security $security,
        #[MapEntity(id: 'idClass')] Classes $classes,
        string $idClass,
        ): array
    {
        $user = $this->getUserFromSecurity($security);
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($user->getSirenCourant());
        $srcUsersInWims = $this->userRepo->findByClass($classes);
        $usersInWims = [];

        foreach ($srcUsersInWims as $currentUser) {
            $usersInWims[$currentUser->getUid()] = $currentUser;
        }

        $uidInWims = array_map(function(User $currentUser) {
            return $currentUser->getUid();
        }, $usersInWims);

        $srcUsersInLdap = $this->ldapService->findStudentsBySirenAndClassName($user->getSirenCourant(), $classes->getName());
        $usersInLdap = [];

        usort($srcUsersInLdap, function(Entry $a, Entry $b) {
            $lastNameComparison = strcmp($a->getAttribute('sn')[0], $b->getAttribute('sn')[0]);

            if ($lastNameComparison === 0) {
                return strcmp($a->getAttribute('givenName')[0], $b->getAttribute('givenName')[0]);
            }

            return $lastNameComparison;
        });

        foreach ($srcUsersInLdap as $currentUser) {
            $usersInLdap[strtolower($currentUser->getAttribute('uid')[0])] = $currentUser;
        }

        $uidInLdap = array_map(function(Entry $entry) {
            return strtolower($entry->getAttribute('uid')[0]);
        }, $usersInLdap);
        $uidCommon = array_intersect($uidInWims, $uidInLdap);

        return [
            'groupingClasses' => $groupingClasses,
            'class' => $classes,
            'usersInWims' => $usersInWims,
            'usersInLdap' => $usersInLdap,
            'uidCommon' => $uidCommon,
        ];
    }
}