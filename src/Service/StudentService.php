<?php
namespace App\Service;

use App\Entity\Cohort;
use App\Entity\User;
use App\Repository\CohortRepository;
use App\Repository\UserRepository;
use Symfony\Component\Ldap\Entry;

/**
 * Service qui va gérer les étudiants
 */
class StudentService
{
    public function __construct(
        private LdapService $ldapService,
        private CohortRepository $cohortRepo,
        private UserRepository $userRepo,
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

    /**
     * Permet de récupérer les étudiant d'une cohort par wims (wims-loader) et
     * par ldap et en fait la différence pour voir si tout est synchronisé
     *
     * @param User $teacher
     * @param Cohort $cohort
     * @return array Un array contenant dans wims la liste des élèves côté wims,
     *  dans ldap la liste des élèves côté ldap et dans sync un bool pour
     *  spécifier si l'on est bien synchronisé.
     */
    public function diffStudentFromTeacherAndCohort(User $teacher, Cohort $cohort): array
    {
        $res = [
            'wims' => [],
            'ldap' => [],
            'wimsUnsync' => [],
            'ldapUnsync' => [],
        ];

        // On récupère les étudiants de la cohorte côté wims (wims-loader)
        $srcUsersInWims = $this->userRepo->findByCohort($cohort);

        foreach ($srcUsersInWims as $user) {
            $res['wims'][$user->getUid()] = [
                'user' => $user,
                'fullName' => $user->getLastName() . ' ' . $user->getFirstName(),
            ];
        }

        $uidInWims = array_keys($res['wims']);

        // On récupère les étudiants de la cohorte côté ldap
        // FIXME: code différent si classe ou groupe pédagogique
        $srcUsersInLdap = $this->ldapService->findStudentsBySirenAndClassName($teacher->getSirenCourant(), $cohort->getName());

        usort($srcUsersInLdap, function(Entry $a, Entry $b) {
            $lastNameComparison = strcmp($a->getAttribute('sn')[0], $b->getAttribute('sn')[0]);

            if ($lastNameComparison === 0) {
                return strcmp($a->getAttribute('givenName')[0], $b->getAttribute('givenName')[0]);
            }

            return $lastNameComparison;
        });

        foreach ($srcUsersInLdap as $user) {
            $res['ldap'][strtolower($user->getAttribute('uid')[0])] = [
                'user' => $user,
                'fullName' => $user->getAttribute('sn')[0] . ' ' . $user->getAttribute('givenName')[0],
            ];
        }

        $uidInLdap = array_keys($res['ldap']);

        // On cherche les différences
        $res['wimsUnsync'] = array_diff($uidInWims, $uidInLdap);
        $res['ldapUnsync'] = array_diff($uidInLdap, $uidInWims);

        return $res;
    }
}