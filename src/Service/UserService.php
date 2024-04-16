<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Filesystem $filesystem = new Filesystem()
    ) {}

    public function updateUser(User $user, array $data): User
    {
        /*if (isset($data['firstName'])) {
            $user->setFirstName()
        }*/
        return $user;
    }
}