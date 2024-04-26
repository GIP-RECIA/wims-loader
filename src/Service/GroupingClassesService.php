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
        private LdapService $ldapService,
        private WimsFileObjectCreatorService $wimsFileObjectCreator
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