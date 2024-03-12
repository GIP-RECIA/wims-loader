<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestRootController extends AbstractController
{
    #[Route(path:"/", name:"root")]
    public function number(): Response
    {
        $number = random_int(0, 100);

        return new Response(
            '<html><body>ok : '.$number . '</body></html>'
        );
    }
}