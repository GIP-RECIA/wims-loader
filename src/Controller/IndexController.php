<?php
namespace App\Controller;

use App\Service\LdapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route(path:"/accueil", name:"accueil")]
    public function number(): Response
    {
        $number = random_int(0, 100);

        $res = '<html>
        <head>
        <base href="/wims-loader/">
        </head>
        <body>ok : '.$number . '<br>
        <a href="' . $this->generateUrl('phpinfo') . '">test page phpinfo</a><br>
        <a href="' . $this->generateUrl('ldap') . '">test page ldap</a><br>
        <a href="' . $this->generateUrl('headers') . '">test page headers</a><br>
        <a href="' . $this->generateUrl('teacher') . '">test page enseignant</a><br>
        <a href="' . $this->generateUrl('eleve') . '">test page élève</a><br>
        </body></html>';


        return new Response($res);
    }

    /**
     * Test du fonctionnement du ldap
     *
     * @param LdapService $ldapService
     * @return Response
     */
    #[Route(path:"/ldap", name:"ldap")]
    public function ldap(LdapService $ldapService): Response
    {
        $results = $ldapService->search("ou=people,dc=esco-centre,dc=fr", "(uid=F20U000A)");

        // Traiter les résultats
        foreach ($results as $entry) {
            dump($entry);
        }

        $results = $ldapService->search("ou=people,dc=esco-centre,dc=fr", "(uid=F20U0001)");
        dump($results->toArray());

        return new Response(
            '<html><body>ok</body></html>'
        );
    }

    /**
     * Affichage du phpinfo
     */
    #[Route(path:"/phpinfo", name:"phpinfo")]
    public function phpinfo(): Response
    {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents ();
        ob_end_clean();

        return new Response(
            $phpinfo
        );
    }

    /**
     * Affichage des headers
     */
    #[Route(path:"/headers", name:"headers")]
    public function headers(): Response
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