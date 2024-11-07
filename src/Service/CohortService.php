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
use Exception;
use Psr\Log\LoggerInterface;
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
        private TeacherService $teacherService,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private WimsFileObjectService $wimsFileObjectService,
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

    /**
     * Fait une synchro des élèves d'une cohorte sans effacer les élèves qui
     * sont en trop. La synchro est réalisé par l'utilisateur spécifié
     * 
     * @param Cohort $cohort
     * @param User $user
     * @return string[] Un message de retour
     */
    public function syncCohort(Cohort $cohort, User $user): array|null
    {
        $message = null;
        $this->logger->info("Synchronisation cohort $cohort for user $user");
        $diffStudents = $this->studentService->diffStudentFromTeacherAndCohort($cohort);

        try {
            if (sizeof($diffStudents['ldapUnsync']) > 0) {
                $this->teacherService->addStudentsInCohort($diffStudents['ldapUnsync'], $cohort);
                $message = ['type' => 'info', 'msg' => $this->translator->trans('teacherZone.message.syncStudentsOk')];
            } else {
                $message = ['type' => 'info', 'msg' => $this->translator->trans('teacherZone.message.noStudentsToSync')];
            }
        } catch (Exception $e) {
            $this->logger->error("Error when synching cohort $cohort for user $user");
            $this->logger->error($e);
            $message = ['type' => 'alert', 'msg' => $this->translator->trans('teacherZone.message.syncStudentsNok')];
        }

        return $message;
    }

    /**
     * Efface tous les utilisateurs de la cohorte.
     * FIXME: cette fonction n'efface pas les liens vers la cohorte dans les fichiers des utilisateurs, il faudra corriger cela
     * 
     * @param Cohort $cohort La cohorte que l'on souhaite vider
     * @return void
     */
    public function emptyCohort(Cohort $cohort): void
    {
        $this->wimsFileObjectService->emptyClasses($cohort);
    }
}