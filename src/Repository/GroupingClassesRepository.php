<?php

namespace App\Repository;

use App\Entity\GroupingClasses;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupingClasses>
 *
 * @method GroupingClasses|null find($id, $lockMode = null, $lockVersion = null)
 * @method GroupingClasses|null findOneBy(array $criteria, array $orderBy = null)
 * @method GroupingClasses[]    findAll()
 * @method GroupingClasses[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupingClassesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupingClasses::class);
    }

    public function findOneByUai(string $uai): ?GroupingClasses
    {
        return $this->findOneBy(['uai' => $uai]);
    }

    public function findOneBySiren(string $siren): ?GroupingClasses
    {
        return $this->findOneBy(['siren' => $siren]);
    }
}
