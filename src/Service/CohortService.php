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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service qui va gérer les Cohorts
 */
class CohortService
{
    public function __construct(
        private GroupingClassesService $groupingClassesService,
        private StudentService $studentService,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function isFullIdConsistent(string $fullId): bool
    {
        return preg_match('/^\d{7}\/\d+$/', $fullId);
    }

    /**
     * Retourne les détails d'une cohorte importée pour pouvoir contrôler
     * les élèves importés dans la cohorte et déclencher une synchro au besoin.
     * 
     * @param Cohort $cohort La cohorte dont on souhaite les détails
     * @return array Les données a afficher sur la cohorte
     */
    public function detailsCohort(Cohort $cohort): array
    {
        $groupingClasses = $cohort->getGroupingClasses();
        $diffStudents = $this->studentService->diffStudentFromTeacherAndCohort($cohort);

        return [
            'groupingClasses' => $groupingClasses,
            'cohort' => $cohort,
            'diffStudents' => $diffStudents,
        ];
    }
}