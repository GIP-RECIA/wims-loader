<?php
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
        private LdapService $ldapService
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
            $results = $this->ldapService->search('ou=structures,dc=esco-centre,dc=fr', "(&(ObjectClass=ENTEtablissement)(ENTStructureSiren=$siren))");
            dump($results);
            dump($results->toArray());
            // TODO: récupérer ESCOStructureNomCourt et ENTStructureUAI
            // TODO: voir pour réduire les données retournées
        }

        return new GroupingClasses();
    }
}