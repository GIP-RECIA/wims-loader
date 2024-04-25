<?php
namespace App\Service;

use App\Entity\Classes;
use App\Entity\User;
use App\Repository\ClassesRepository;
use App\Repository\GroupingClassesRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

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
    ) {}

    public function createClass(User $teacher, string $className): Classes
    {
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($teacher->getSirenCourant());
        $class = $this->classRepository->getClassByGroupingClassesTeacherAndName($groupingClasses, $teacher, $className);

        if ($class === null) {
            $isTeacherRegistered = $this->groupingClassesRepo->isTeacherRegistered($groupingClasses, $teacher);

            // Si l'enseignant n'est pas enregistré dans l'établissement, on l'enregistre
            if (!$isTeacherRegistered) {
                $groupingClasses->addRegisteredTeacher($teacher);
                $this->wims->createTeacherInGroupingClassesFromObj($teacher, $groupingClasses);
                // TODO: voir si le flush est utile là
                $this->em->flush();
            }

            $class = (new Classes())
                ->setTeacher($teacher)
                ->setGroupingClasses($groupingClasses);
            // Création de la classe côté wims
            $class = $this->wims->createClassInGroupingClassesFromObj($class);
            $this->em->persist($class);
            dump($class);

            // Récupération des élèves dans le ldap
            $uidStudents = $this->studentService->getListUidStudentFromSirenAndClassName(
                $groupingClasses->getSiren(),
                $className);
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

            foreach ($students as $studentsBdd) {
                $class->addStudent($student);
                // Inscription des élèves côté wims
                $this->wims->addUserInClassFromObj($student, $class);
                dump($student);
            }
    
            $this->em->persist($class);
            $this->em->flush();
        }

        dump($class);
        return $class;
    }
}