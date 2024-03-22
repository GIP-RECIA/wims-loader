<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HeadersController extends AbstractController
{
    #[Route(path:"/headers", name:"headers")]
    public function phpinfo(): Response
    {
        ob_start();
        var_dump($_SERVER);
        $headers = ob_get_contents();
        ob_end_clean();

        return new Response(
            $headers
        );
    }
}