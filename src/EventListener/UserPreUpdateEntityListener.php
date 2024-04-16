<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Filesystem\Filesystem;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
class UserPreUpdateEntityListener
{

    public function __construct(
        private Filesystem  $filesystem = new Filesystem()
    ) {
    }
    public function preUpdate(User $user, PreUpdateEventArgs $args): void
    {
        $dataChange = $args->getEntityChangeSet();
        // je récupère un tableau du genre ['firstName' => ['ancien', 'nouveau']]
        dump($dataChange);
        // TODO: écrire les traitements
    }
}