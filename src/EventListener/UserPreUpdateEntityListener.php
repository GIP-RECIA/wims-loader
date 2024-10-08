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
namespace App\EventListener;

use App\Entity\User;
use App\Repository\CohortRepository;
use App\Repository\GroupingClassesRepository;
use App\Service\WimsFileObjectService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
class UserPreUpdateEntityListener
{

    public function __construct(
        private GroupingClassesRepository $groupingClassesRepo,
        private CohortRepository $cohortRepo,
        private WimsFileObjectService $wimsFileObjectCreatorService,
        private LoggerInterface $logger,
    ) {
    }
    public function preUpdate(User $user, PreUpdateEventArgs $args): void
    {
        $dataChange = $args->getEntityChangeSet();
        $this->logger->info("Update user data : " . json_encode($dataChange));
        $firstName = $this->getValueOrDefault($dataChange, 'firstName', $user->getFirstName());
        $lastName = $this->getValueOrDefault($dataChange, 'lastName', $user->getLastName());
        $mail = $this->getValueOrDefault($dataChange, 'mail', $user->getMail());

        // Récupérer les établissements en tant qu'enseignant et boucler
        $idWimsGroupingClasses = $this->groupingClassesRepo->findIdWimsGroupingClassesByTeacher($user);

        // Mettre à jour l'enseignant dans tous les établissement où il est
        //  présent dans le fichier .teacherlist
        foreach ($idWimsGroupingClasses as $idWims) {
            $this->wimsFileObjectCreatorService
                ->replaceFirstNameAndLastNameInTeacherList(
                    $idWims,
                    $user->getUid(),
                    $firstName,
                    $lastName,
                );
        }

        $idsWims = $this->cohortRepo->findFullWimsIdOfStudentClass($user);
        $lastIdWimsGroupingClasses = null;

        foreach ($idsWims as $idWims) {
            $idWimsGroupingClasses = $idWims['idWimsGroupingClasses'];
            $idWimsClasses = $idWims['idWimsClasses'];

            // Si on est sur un établissement que l'on n'a pas encore traité
            //  On réalise les modifications du userlist
            if ($lastIdWimsGroupingClasses != $idWimsGroupingClasses) {
                $lastIdWimsGroupingClasses = $idWimsGroupingClasses;

                $this->wimsFileObjectCreatorService
                    ->replaceFirstNameAndLastNameInUserList(
                        $idWimsGroupingClasses,
                        $user->getUid(),
                        $firstName,
                        $lastName,
                    );
                

                // Et on fini par le fichier ayant le nom de l'uid
                $this->wimsFileObjectCreatorService
                ->replaceDataInUidFile(
                    $idWimsGroupingClasses . '/.users/' . $user->getUid(),
                    $firstName, $lastName, $mail
                );
            }

            // Puis on traite le userlist de la classe
            $this->wimsFileObjectCreatorService
                ->replaceFirstNameAndLastNameInUserList(
                    $idWimsGroupingClasses . '/' . $idWimsClasses,
                    $user->getUid(),
                    $firstName,
                    $lastName,
                );

        }
    }

    private function getValueOrDefault(array $array, string $key, string $default = null): string
    {
        return array_key_exists($key, $array) ? $array[$key][1] : $default;
    }
}