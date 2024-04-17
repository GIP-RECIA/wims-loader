<?php
namespace App\Service;

use App\Entity\GroupingClasses;
use App\Exception\InvalidGroupingClassesException;
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
            $data = $this->ldapService->search('ou=structures,dc=esco-centre,dc=fr', "(&(ObjectClass=ENTEtablissement)(ENTStructureSiren=$siren))");
            $results = $data->toArray();

            if (count($results) === 0) {
                throw new InvalidGroupingClassesException(
                    "Le groupement de classes (établissement) avec le siren '$siren' n'existe pas dans le ldap"
                );
            }

            $res = $results[0];
            $groupingClassesName = $res->getAttribute('ESCOStructureNomCourt')[0];
            $uai = $res->getAttribute('ENTStructureUAI')[0];
            $dataGroupingClasses = ['institution_name' => $groupingClassesName, 'description' => $uai];
            $groupingClassesIdWims = $this->wimsFileObjectCreator->createNewGroupingClasses([], $dataGroupingClasses);
            $groupingClasses = new GroupingClasses();
            $groupingClasses
                ->setUai($uai)
                ->setSiren($siren)
                ->setIdWims($groupingClassesIdWims)
                ->setName($groupingClassesName);
            $this->em->persist($groupingClasses);
            $this->em->flush();
        }

        return $groupingClasses;
    }
}