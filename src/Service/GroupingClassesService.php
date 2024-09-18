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

use App\Entity\GroupingClasses;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service qui va gérer les GroupingClasses (les établissements)
 */
class GroupingClassesService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LdapService $ldapService,
        private WimsFileObjectService $wimsFileObjectCreator
    ) {}

    /**
     * Permet de charger un GroupingClasses et le créer au besoin
     *
     * @param string $siren
     * @return GroupingClasses
     */
    public function loadGroupingClasses(string $siren): GroupingClasses
    {
        $groupingClasses = $this->em->getRepository(GroupingClasses::class)->findOneBySiren($siren);

        if ($groupingClasses === null) {
            $res = $this->ldapService->findOneGroupingClassesBySiren($siren);
            $groupingClasses = (new GroupingClasses())
                ->setSiren($siren)
                ->setUai($res->getAttribute('ENTStructureUAI')[0])
                ->setName($res->getAttribute('ESCOStructureNomCourt')[0]);
            $groupingClasses = $this->wimsFileObjectCreator->createNewGroupingClassesFromObj($groupingClasses);
            $this->em->persist($groupingClasses);
            $this->em->flush();
        }

        return $groupingClasses;
    }
}