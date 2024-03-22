<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route(path:"/test", name:"test")]
    public function test(Security $security): Response
    {
        $number = random_int(0, 100);
        $user = $security->getUser();

        return new Response(
            '<html><body>user: ' . $user->getUserIdentifier().'<br>'
            . $number . '</body></html>'
        );
    }
}