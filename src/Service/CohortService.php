<?php
namespace App\Service;

use App\Entity\User;

//use Doctrine\ORM\EntityManagerInterface;

/**
 * Service qui va gérer les Cohorts
 */
class CohortService
{
    public function generateName(string $baseCohortName, User $teacher): string
    {
        return mb_substr($baseCohortName, 0, 50);
    }
}