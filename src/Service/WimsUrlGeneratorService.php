<?php
namespace App\Service;

use App\Entity\Classes;
use App\Entity\GroupingClasses;
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
        $params = $this->config['params_url'];
        $params['class'] = $class->getFullIdWims();
        $wimsUrl = $this->generateWimsUrl($params);

        return $this->config['cas'] . '/login?service=' . urlencode($wimsUrl);
    }

    /**
     * Génère l'url enseignant d'un établissement a partir de l'objet de l'établissement
     *
     * @param GroupingClasses $groupingClass L'établissement
     * @return string L'url enseignant de l'établissement
     */
    public function wimsUrlGroupingClassesForTeacher(GroupingClasses $groupingClass): string
    {
        $params = $this->config['params_url'];
        $params['class'] = $groupingClass->getIdWims();
        $wimsUrl = $this->generateWimsUrl($params);

        return $this->config['cas'] . '/login?service=' . urlencode($wimsUrl);
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