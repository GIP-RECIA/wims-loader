<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\CohortRepository;
use App\Repository\GroupingClassesRepository;
use App\Service\WimsFileObjectCreatorService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
class UserPreUpdateEntityListener
{

    public function __construct(
        private GroupingClassesRepository $groupingClassesRepo,
        private CohortRepository $classesRepo,
        private WimsFileObjectCreatorService $wimsFileObjectCreatorService,
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

        $idsWims = $this->classesRepo->findFullWimsIdOfStudentClass($user);
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