<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\ClassesRepository;

/**
 * Service qui va gérer les étudiants
 */
class StudentService
{
    public function __construct(
        private LdapService $ldapService,
        private ClassesRepository $classRepo,
    ) {}

    /**
     * Donne la liste des uid des élèves a partir d'un siren et d'un nom de class
     *
     * @param string $siren Le siren de l'établissement
     * @param string $class Le nom de la class
     * @return array La liste des uid des élèves de la class
     */
    public function getListUidStudentFromSirenAndClassName(string $siren, string $class): array
    {
        $results = $this->ldapService->findStudentsBySirenAndClassName($siren, $class);
        $res = [];

        foreach ($results as $result) {
            $res[] = strtolower($result->getAttribute('uid')[0]);
        }

        return $res;
    }

    /**
     * Retourne un tableau des données des élèves dont les uid ont été fournit
     *
     * @param string[] $arrUid Les uid des élèves que l'on recherche
     * @return User[] Les élèves sous forme d'User non persisté
     */
    public function getListStudentFromUidList(array $arrUid): array
    {
        $results = $this->ldapService->findUsersByUid($arrUid);
        $users = [];

        foreach ($results as $result) {
            $users[] = (new User())
                ->setUid(strtolower($result->getAttribute('uid')[0]))
                ->setFirstName($result->getAttribute('givenName')[0])
                ->setLastName($result->getAttribute('sn')[0])
                ->setMail($result->getAttribute('mail')[0]);
        }

        return $users;
    }

}