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
use App\Repository\GroupingClassesRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Exception\AlreadyExistsException;

/**
 * Service qui va gérer les enseignants
 */
class TeacherService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GroupingClassesService $groupingClassesService,
        private StudentService $studentService,
        private UserRepository $userRepo,
        private GroupingClassesRepository $groupingClassesRepo,
        private CohortRepository $cohortRepo,
        private WimsFileObjectService $wims,
        private CohortNameService $cohortNameService,
        private LdapService $ldapService,
    ) {}

    /**
     * Permet de récupérer les classes et les groupes pédagogiques d'un
     * enseignant
     *
     * @param User $teacher L'enseignant
     * @return array 'classes' Les classes, 'groups' Les groupes pédagogiques
     */
    public function getCohortsOfTeacher(User $teacher): array
    {
        $res = ['classes' => [], 'groups' => []];
        $dataLdap = $this->ldapService->findOneUserByUid($teacher->getUid());
        $src = [
            'classes' => $dataLdap->getAttribute('ENTAuxEnsClasses'),
            'groups' => $dataLdap->getAttribute('ENTAuxEnsGroupes'),
        ];
        $start = "ENTStructureSIREN=" . $teacher->getSirenCourant() . ",ou=structures,dc=esco-centre,dc=fr$";
        $lengthStart = mb_strlen($start);
        
        foreach ($src as $key => $value) {
            if (null !== $value) {
                foreach ($value as $line) {
                    if (str_starts_with($line, $start)) {
                        $res[$key][] = mb_substr($line, $lengthStart);
                    }
                }
            }
        }

        return $res;
    }

    public function createCohort(User $teacher, string $cohortName, CohortType $type): Cohort
    {
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($teacher->getSirenCourant());
        $cohort = $this->cohortRepo->findOneByGroupingClassesTeacherAndName($groupingClasses, $teacher, $cohortName);
        $structure = "ENTStructureSIREN=" . $teacher->getSirenCourant() . ",ou=structures,dc=esco-centre,dc=fr";

        if ($cohort !== null) {
            throw new AlreadyExistsException(
                "La cohorte \"$cohortName\" de type " . Cohort::cohortTypeString($type)
                . " pour l'enseignant \"" . $teacher->getFullName() . "\" existe déjà."
            );
        }

        $isTeacherRegistered = $this->groupingClassesRepo->isTeacherRegistered($groupingClasses, $teacher);

        // Si l'enseignant n'est pas enregistré dans l'établissement, on l'enregistre
        if (!$isTeacherRegistered) {
            $groupingClasses->addRegisteredTeacher($teacher);
            $this->wims->createTeacherInGroupingClassesFromObj($teacher, $groupingClasses);
        }

        // On récupère les matières de cet enseignant pour cette classe
        $res = $this->ldapService->findOneUserByUid($teacher->getUid());
        $attrMatiere = $type === CohortType::TYPE_CLASS ? "ENTAuxEnsClassesMatieres" : "ENTAuxEnsGroupesMatieres";
        $resCodeSubjects = $res->getAttribute($attrMatiere);
        $resSubjects = $res->getAttribute("ESCOAuxEnsCodeMatiereEnseignEtab");
        $subjects = [];
        $fullCohortName = $structure . "$" . $cohortName . "$";

        foreach ($resCodeSubjects as $codeSubject) {
            if (str_starts_with($codeSubject, $fullCohortName)) {
                $codeSubjects = mb_substr($codeSubject, mb_strlen($fullCohortName));
                $startWith = $structure . "$" . $codeSubjects . "$";

                foreach ($resSubjects as $subject) {
                    if (str_starts_with($subject, $startWith)) {
                        $subjects[] = mb_substr($subject, mb_strlen($startWith));
                    }
                }
            }
        }

        $cohort = (new Cohort())
            ->setTeacher($teacher)
            ->setGroupingClasses($groupingClasses)
            ->setName($this->cohortNameService->generateName($cohortName, $teacher))
            ->setSubjects(mb_substr(implode(', ', $subjects), 0, 255))
            ->setType($type)
            ->setLastSyncAt();
        // Création côté wims d'une cohorte
        $cohort = $this->wims->createCohortInGroupingClassesFromObj($cohort);
        $this->em->persist($cohort);

        // Récupération des élèves dans le ldap
        $uidStudents = $this->studentService->getListUidStudentFromSirenAndCohortName(
            $groupingClasses->getSiren(),
            $cohortName,
            $type
        );
        // Ajout des élèves dans la cohorte
        $this->commonAddStudentsInCohort($uidStudents, $cohort);

        $this->em->flush();

        return $cohort;
    }

    /**
     * Ajoute les élèves dans la cohorte
     *
     * @param array $uidStudents La liste des uid des élèves à ajouter
     * @param Cohort $cohort La cohorte dans laquelle ajouter les élèves
     * @return void
     */
    public function addStudentsInCohort(array $uidStudents, Cohort $cohort): void
    {
        $this->commonAddStudentsInCohort($uidStudents, $cohort);
        $this->em->flush();
    }

    /**
     * Partie commune des fonctions permettant d'ajouter des élèves dans une cohorte
     *
     * @param array $uidStudents La liste des uid des élèves à ajouter
     * @param Cohort $cohort La cohorte dans laquelle ajouter les élèves
     * @return void
     */
    private function commonAddStudentsInCohort(array $uidStudents, Cohort $cohort): void
    {
        $studentsBdd = $this->userRepo->findByUid($uidStudents);
        $students = [];
        $studentsToCreate = [];
    
        foreach ($studentsBdd as $student) {
            $students[$student->getUid()] = $student;
        }
    
        foreach ($uidStudents as $uidStudent) {
            if (!array_key_exists($uidStudent, $students)) {
                $studentsToCreate[] = $uidStudent;
            }
        }

        $studentsToCreate = $this->studentService->getListStudentFromUidList($studentsToCreate);

        foreach ($studentsToCreate as $student) {
            $this->em->persist($student);
            $students[$student->getUid()] = $student;
        }

        foreach ($students as $student) {
            // Inscription des élèves côté wims
            $this->wims->addUserInClassFromObj($student, $cohort);
        }
    }
}