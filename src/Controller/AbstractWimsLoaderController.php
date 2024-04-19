<?php
namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;

abstract class AbstractWimsLoaderController extends AbstractController
{
    protected function getUserFromSecurity(Security $security): User
    {
        $user = $security->getUser();

        // Un user est forcément de la class User
        if (!($user instanceof User)) {
            throw new \Exception("Le user devrait être de type User.");
        }

        return $user;
    }
}