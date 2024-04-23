<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\ClassesRepository;
use App\Repository\GroupingClassesRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service qui va gérer les enseignants
 */
class TeacherService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GroupingClassesService $groupingClassesService,
        private GroupingClassesRepository $groupingClassesRepo,
        private ClassesRepository $classRepository,
        private WimsFileObjectCreatorService $wims,
    ) {}

    public function createClass(User $teacher, string $className): void
    {
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($teacher->getSirenCourant());
        $class = $this->classRepository->getClassByGroupingClassesTeacherAndName($groupingClasses, $teacher, $className);

        if ($class === null) {
            $isTeacherRegistered = $this->groupingClassesRepo->isTeacherRegistered($groupingClasses, $teacher);

            // Si l'enseignant n'est pas enregistré dans l'établissement, on l'enregistre
            if (!$isTeacherRegistered) {
                $groupingClasses->addRegisteredTeacher($teacher);
                $this->wims->createTeacherInGroupingClassesFromObj($teacher, $groupingClasses);
                $this->em->flush();
            }
        }
        
        // TODO: créer la classe
        // TODO: inscrire les élèves
    }
}