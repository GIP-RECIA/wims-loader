<?php
namespace App\Service;

use App\Entity\GroupingClasses;
use App\Entity\User;
use App\Repository\GroupingClassesRepository;

/**
 * Service qui va gérer les enseignants
 */
class TeacherService
{
    public function __construct(
        private GroupingClassesService $groupingClassesService,
        private GroupingClassesRepository $groupingClassesRepo,
    ) {}

    public function createClass(User $teacher, string $className): void
    {
        $groupingClasses = $this->groupingClassesService->loadGroupingClasses($teacher->getSirenCourant());

        /**
         * Je dois d'abord voir si le groupingClass existe.
         * donc je le requête avec le siren ?
         * 
         * Si il n'existe pas, je le créé avec les données du ldap
         * 
         * Ensuite je dois tester si la class n'existe pas déjà ?
         * Je la requête avec le className, le groupingClasses et le teacher
         * 
         * Si elle n'existe aps déjà, je la créé
         */
    }
}