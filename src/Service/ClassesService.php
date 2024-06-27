<?php
namespace App\Service;

use App\Entity\User;

//use Doctrine\ORM\EntityManagerInterface;

/**
 * Service qui va gérer les Classes (les classes)
 */
class ClassesService
{
    public function generateName(string $baseClassName, User $teacher): string
    {
        return mb_substr($baseClassName, 0, 50);
    }
}