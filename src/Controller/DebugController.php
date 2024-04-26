<?php
namespace App\Controller;

use App\Service\LdapService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DebugController extends AbstractWimsLoaderController
{
    public function __construct(
        private LdapService $ldapService,
    ) {}

    #[Route(path:"/debug/", name:"accueil")]
    #[Template('web/debug.html.twig')]
    public function debug(): array
    {
        return [];
    }

    /**
     * Affichage des headers
     */
    #[Route(path:"/debug/infos", name:"debug_infos")]
    #[Template('web/debug.html.twig')]
    public function infos(Security $security): array
    {
        $user = $this->getUserFromSecurity($security);
        $userLdap = $this->ldapService->findOneStudentByUid($user->getUid());
        
        return [
            'dumpArray' => [
                'Utilisateur' => $user,
                'Données du ldap sur l\'utilisateur' => $userLdap,
            ],
        ];
    }

    /**
     * Affichage du phpinfo
     */
    #[Route(path:"/debug/phpinfo", name:"debug_phpinfo")]
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
    #[Route(path:"/debug/headers", name:"debug_headers")]
    #[Template('web/debug.html.twig')]
    public function headers(): array
    {
        return [
            'dump' => $_SERVER,
        ];
    }
}