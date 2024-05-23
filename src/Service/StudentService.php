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

        foreach ($results as $result) {
            $res[] = strtolower($result->getAttribute('uid')[0]);
        }

        return $res;
    }

    /**
     * Retourne la liste des noms de class pour un élève dans son établissement courant
     *
     * @param User $user L'élève dont on cherche les classes
     * @return array La liste des classes
     */
    public function getListClassNameFromSirenAndUidStudent(User $user): array
    {
        $sirenCourant = $user->getSirenCourant();
        $attributes = $this->ldapService->findOneStudentByUid($user->getUid())->getAttributes();
        $res = [];
        $startStrClass = "ENTStructureSIREN=$sirenCourant,ou=structures,dc=esco-centre,dc=fr$";
        $srcClass = $attributes['ENTEleveClasses'];

        foreach ($srcClass as $strClass) {
            if (str_starts_with($strClass, $startStrClass)) {
                $res[] = substr($strClass, strlen($startStrClass));
            }
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