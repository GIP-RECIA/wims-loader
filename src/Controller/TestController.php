<?php
namespace App\Controller;

use App\Entity\User;
use App\Service\GroupingClassesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TestController extends AbstractController
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private GroupingClassesService $groupingClassesService
    ) {}

    #[Route(path:"/test", name:"test")]
    public function test(Security $security): Response
    {
        $user = $security->getUser();
        //$token = $security->getToken();

        // Un user est forcément de la class User
        if (!($user instanceof User)) {
            throw new \Exception("Le user devrait être de type User.");
        }

        $response = '<html><body>user: ' . $user;

        if ($this->authorizationChecker->isGranted('ROLE_ENS')) {
            $groupingClasses = $this->groupingClassesService->loadGroupingClasses($user->getSirenCourant());
            $response .= '<br>' . $groupingClasses;
        }

        $response .= '</body></html>';

        return new Response($response);

    }
}