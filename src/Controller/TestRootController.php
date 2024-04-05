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

        $res = '<html>
        <head>
        <base href="/wims-loader/">
        </head>
        <body>start0<br>ok : '.$number . '<br>
        <a href="' . $this->generateUrl('phpinfo') . '">test page phpinfo</a><br>
        <a href="' . $this->generateUrl('test') . '">test page protégé</a><br>
        <a href="' . $this->generateUrl('ldap') . '">test page ldap</a><br>
        </body></html>';


        return new Response($res);
    }
}