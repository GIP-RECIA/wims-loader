<?php
namespace App\Controller;

use App\Service\GroupingClassesService;
use App\Service\LdapService;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RootController extends AbstractWimsLoaderController
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private LdapService $ldapService,
        private GroupingClassesService $groupingClassesService,
        private TranslatorInterface $translator,
    ) {}

    #[Route(path:"/home", name:"home")]
    public function home(Security $security): Response
    {
        $user = $this->getUserFromSecurity($security);
        $isTeacher = $this->authorizationChecker->isGranted('ROLE_ENS');
        $isStudent = $this->authorizationChecker->isGranted('ROLE_ELV');
        $isAdmin = $this->authorizationChecker->isGranted('ROLE_ADMIN');
        $groupingClasses = null;

        if ($isTeacher && !$isStudent) {
            return $this->redirectToRoute('teacher');
        } else if ($isStudent) {
            return $this->redirectToRoute('student');
        } else if ($isAdmin) {
            return $this->redirectToRoute('adminCohorts');
        }

        try {
            $groupingClasses = $this->groupingClassesService->loadGroupingClasses($user->getSirenCourant());
        } catch (Exception $e) {}
        
        $navigationBar = [['name' => $this->translator->trans('menu.home')]];

        return $this->render('web/home.html.twig', [
            'groupingClasses' => $groupingClasses,
            'navigationBar' => $navigationBar,
        ]);
    }

    /**
     * Permet de se déconnecter et de retourner au portail
     *
     * @param Security $security
     * @return Response
     */
    #[Route('/logout', name:'logout')]
    public function logout(Security $security): Response
    {
        $security->logout(false);

        return $this->redirect('/');
    }
}