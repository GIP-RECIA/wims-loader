<?php
namespace App\Service;

use App\Entity\Classes;
use App\Entity\User;
use App\Repository\ClassesRepository;
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
        private ClassesRepository $classRepository,
        private WimsFileObjectCreatorService $wims,
        private ClassesService $classesService,
        private LdapService $ldapService,
    ) {}

    public function createClass(User $teacher, string $className): Classes
    {
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($teacher->getSirenCourant());
        $class = $this->classRepository->findOneByGroupingClassesTeacherAndName($groupingClasses, $teacher, $className);
        $structure = "ENTStructureSIREN=" . $teacher->getSirenCourant() . ",ou=structures,dc=esco-centre,dc=fr";

        if ($class !== null) {
            throw new AlreadyExistsException(
                "La classe \"" . $className . "\" pour l'enseignant \"" .
                $teacher->getFullName() . "\" existe déjà."
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
        $resCodeSubjects = $res->getAttribute("ENTAuxEnsClassesMatieres");
        $resSubjects = $res->getAttribute("ESCOAuxEnsCodeMatiereEnseignEtab");
        $subjects = [];
        $fullClassName = $structure . "$" . $className . "$";

        foreach ($resCodeSubjects as $codeSubject) {
            if (str_starts_with($codeSubject, $fullClassName)) {
                $codeSubjects = substr($codeSubject, strlen($fullClassName));
                $startWith = $structure . "$" . $codeSubjects . "$";

                foreach ($resSubjects as $subject) {
                    if (str_starts_with($subject, $startWith)) {
                        $subjects[] = substr($subject, strlen($startWith));
                    }
                }
            }
        }

        $class = (new Classes())
            ->setTeacher($teacher)
            ->setGroupingClasses($groupingClasses)
            ->setName($this->classesService->generateName($className, $teacher))
            ->setSubjects(substr(implode(', ', $subjects), 0, 255))
            ->setLastSyncAt();
        // Création de la classe côté wims
        $class = $this->wims->createClassInGroupingClassesFromObj($class);
        $this->em->persist($class);

        // Récupération des élèves dans le ldap
        $uidStudents = $this->studentService->getListUidStudentFromSirenAndClassName(
            $groupingClasses->getSiren(),
            $className);
        // Ajout des élèves dans la classe
        $this->commonAddStudentsInClass($uidStudents, $class);

        $this->em->flush();

        return $class;
    }

    /**
     * Ajoute les élèves dans la classe
     *
     * @param array $uidStudents La liste des uid des élèves à ajouter
     * @param Classes $class La classe dans laquelle ajouter les élèves
     * @return void
     */
    public function addStudentsInClass(array $uidStudents, Classes $class): void
    {
        $this->commonAddStudentsInClass($uidStudents, $class);
        $this->em->flush();
    }

    /**
     * Partie commune des fonctions permettant d'ajouter des élèves dans une classe
     *
     * @param array $uidStudents La liste des uid des élèves à ajouter
     * @param Classes $class La classe dans laquelle ajouter les élèves
     * @return void
     */
    private function commonAddStudentsInClass(array $uidStudents, Classes $class): void
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
            $class->addStudent($student);
            // Inscription des élèves côté wims
            $this->wims->addUserInClassFromObj($student, $class);
        }
    }
}