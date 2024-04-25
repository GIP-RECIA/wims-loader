<?php
namespace App\Service;

use App\Entity\Classes;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WimsUrlGeneratorService
{
    public function __construct(
        private array $config,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * Génère l'url élève d'une classe a partir de l'objet de la classe
     *
     * @param Classes $class La classe
     * @return string L'url élève de la classe
     */
    public function wimsUrlClassForStudent($class): string
    {
        $params = $this->config['params_url_class_for_student'];
        $params['class'] = $class->getFullIdWims();
        $wimsUrl = $this->generateWimsUrl($params);

        return $this->config['cas'] . '/login?service=' . urlencode($wimsUrl);
    }

    /**
     * Génère l'url élève d'une classe a partir de l'objet de la classe
     *
     * @param Classes $class La classe
     * @return string L'url élève de la classe
     */
    public function wimsUrlClassForTeacher($class): string
    {
        $params = $this->config['params_url_class_for_teacher'];
        $params['class'] = $class->getFullIdWims();
        return $this->generateWimsUrl($params);
    }


    /**
     * Génère une url wims avec le bon domaine automatiquement
     *
     * @param string[] $params Le tableau des paramètres
     * @return string L'url wims
     */
    private function generateWimsUrl(array $params): string
    {
        $url = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost()
            . "/wims/wims.cgi";
        $firstParam = true;

        foreach ($params as $key => $value) {
            if ($firstParam) {
                $firstParam = false;
                $url .= "?";
            } else {
                $url .= "&";
            }

            $url .= $key . "=" . $value;
        }

        return $url;
    }
}