<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Service qui va gérer les étudiants
 */
class StudentsService
{
    public function __construct(
        private LdapService $ldapService
    ) {}

    public function getListUidStudentFromSirenAndClassName(string $siren, string $class): array
    {
        $data = $this->ldapService->search('ou=people,dc=esco-centre,dc=fr', "(ENTEleveClasses=ENTStructureSIREN=$siren,ou=structures,dc=esco-centre,dc=fr\$$class)");
        $results = $data->toArray();
        $res = [];

        foreach ($results as $result) {
            $res[] = $result->getAttribute('uid')[0];
        }

        return $res;
    }

}