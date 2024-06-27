<?php
/**
 * Copyright © 2024 GIP-RECIA (https://www.recia.fr/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace App\Service;

use App\Entity\Cohort;
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
     * Génère l'url élève d'une classe wims a partir de l'objet de la cohort
     *
     * @param Cohort $cohort La cohorte
     * @return string L'url élève de la classe
     */
    public function wimsUrlClassForStudent(Cohort $cohort): string
    {
        $params = $this->config['params_url'];
        $params['class'] = $cohort->getFullIdWims();
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