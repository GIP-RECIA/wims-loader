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
        $user = $security->getUser();
        //$token = $security->getToken();
        //dump($token) ;
        dump($user);

        return new Response(
            '<html><body>user: ' . $user . '</body></html>'
        );
    }
}