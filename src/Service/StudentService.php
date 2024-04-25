<?php
namespace App\Service;

use App\Entity\User;
use App\Exception\LdapResultException;

/**
 * Service qui va gérer les étudiants
 */
class StudentService
{
    public function __construct(
        private LdapService $ldapService
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
        $data = $this->ldapService->search('ou=people,dc=esco-centre,dc=fr', "(ENTEleveClasses=ENTStructureSIREN=$siren,ou=structures,dc=esco-centre,dc=fr\$$class)");
        $results = $data->toArray();
        $res = [];

        foreach ($results as $result) {
            $res[] = $result->getAttribute('uid')[0];
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
        $uid = $user->getUid();
        $sirenCourant = $user->getSirenCourant();
        $results = ($this->ldapService->search('ou=people,dc=esco-centre,dc=fr', "(uid=$uid)"))->toArray();
        $count = count($results);

        if ($count !== 1) {
            throw new LdapResultException("Le résultat de la requête ldap devrait contenir les données d'un utilisateur, mais elle en à $count");
        }

        $res = [];
        $startStrClass = "ENTStructureSIREN=$sirenCourant,ou=structures,dc=esco-centre,dc=fr$";
        $srcClass = $results[0]->getAttribute('ENTEleveClasses');

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
        $strSearch = '';

        foreach ($arrUid as $uid) {
            $strSearch .= "(uid=$uid)";
        }

        $strSearch = "(|" . $strSearch . ")";
        $results = ($this->ldapService->search('ou=people,dc=esco-centre,dc=fr', $strSearch))->toArray();
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