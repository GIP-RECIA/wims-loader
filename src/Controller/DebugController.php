<?php
namespace App\Controller;

use App\Repository\ClassesRepository;
use App\Repository\GroupingClassesRepository;
use App\Repository\UserRepository;
use App\Service\LdapService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
#[IsGranted('IS_DEV_ENV')]
class DebugController extends AbstractWimsLoaderController
{
    public function __construct(
        private LdapService $ldapService,
        private UserRepository $userRepo,
        private GroupingClassesRepository $groupingClassesRepo,
        private ClassesRepository $classesRepo,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Affichage des headers
     */
    #[Route(path:"/debug/infos", name:"debug_user")]
    #[Template('web/debug.html.twig')]
    public function infos(Security $security): array
    {
        $user = $this->getUserFromSecurity($security);
        $userLdap = $this->ldapService->findOneUserByUid($user->getUid());
        $userBdd = $this->userRepo->findOneByUid($user->getUid());
        $groupingClasses = $this->groupingClassesRepo->findOneBySiren($user->getSirenCourant());
        $classesStudentBdd = $this->classesRepo->findByGroupingClassesAndStudent($groupingClasses, $user);
        $classesTeacherBdd = $this->classesRepo->findByGroupingClassesAndTeacher($groupingClasses, $user);
        $navigationBar = [['name' => $this->translator->trans('menu.debug')]];
        
        return [
            'navigationBar' => $navigationBar,
            'dumpArray' => [
                'Utilisateur' => $user,
                'Données du ldap sur l\'utilisateur' => $userLdap,
                'Données de la bdd sur l\'utilisateur' => $userBdd,
                'Données de la bdd sur l\'établissement' => $groupingClasses,
                'Données de la bdd sur les classes de l\'utilisateur en tant qu\'élève' => $classesStudentBdd,
                'Données de la bdd sur les classes de l\'utilisateur en tant qu\'enseignant' => $classesTeacherBdd,
            ],
        ];
    }

    /**
     * Affichage du phpinfo
     */
    #[Route(path:"/debug/phpinfo", name:"debug_phpinfo")]
    #[Template('web/debug.html.twig')]
    public function phpinfo(): array
    {
        $navigationBar = [['name' => $this->translator->trans('menu.debug')]];
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents ();
        ob_end_clean();

        return [
            'navigationBar' => $navigationBar,
            'content' => $phpinfo,
        ];
    }

    /**
     * Affichage des headers
     */
    #[Route(path:"/debug/headers", name:"debug_headers")]
    #[Template('web/debug.html.twig')]
    public function headers(): array
    {
        $navigationBar = [['name' => $this->translator->trans('menu.debug')]];

        return [
            'navigationBar' => $navigationBar,
            'dumpArray' => [
                'headers' => $_SERVER,
            ]
        ];
    }
}