<?php
namespace App\Controller;

use App\Service\LdapService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RootController extends AbstractWimsLoaderController
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private LdapService $ldapService,
    ) {}

    #[Route(path:"/", name:"root")]
    public function root(Security $security): Response
    {
        /*$results = $this->ldapService->findFake();
        $res = [];
        $resTxt = "";

        foreach ($results as $result) {
            $uid = strtolower($result->getAttribute('uid')[0]);
            $res[] = $uid;
            $resTxt .= $uid."\n";
        }

        return new Response($resTxt);*/

        
        $user = $this->getUserFromSecurity($security);
        $isTeacher = $this->authorizationChecker->isGranted('ROLE_ENS');
        $isStudent = $this->authorizationChecker->isGranted('ROLE_ELV');

        if ($isTeacher && !$isStudent) {
            return $this->redirectToRoute('teacher');
        }/* else if ($isStudent && !$isTeacher) {
            return $this->redirectToRoute('student');
        }*/

        return $this->render('web/root.html.twig');
    }
}