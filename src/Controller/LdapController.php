<?php
namespace App\Controller;

use App\Service\LdapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LdapController extends AbstractController
{
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
}