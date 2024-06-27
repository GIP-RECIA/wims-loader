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
use App\Entity\User;
use App\Enum\CohortType;
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
     * Donne la liste des uid des élèves a partir d'un siren, d'un nom de cohorte
     * et du type de cohorte
     *
     * @param string $siren Le siren de l'établissement
     * @param string $class Le nom de la class
     * @param CohortType $type Le type de la cohorte
     * @return array La liste des uid des élèves de la class
     */
    public function getListUidStudentFromSirenAndCohortName(string $siren, string $class, CohortType $type): array
    {
        $results = $this->ldapService->findStudentsBySirenAndCohortName($siren, $class, $type);
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
        $srcUsersInLdap = $this->ldapService->findStudentsBySirenAndCohortName($teacher->getSirenCourant(), $cohort->getName(), $cohort->getType());

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