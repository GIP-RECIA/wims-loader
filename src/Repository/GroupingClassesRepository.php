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
        return $this->createQueryBuilder('gc')
            ->where('gc.uai = :uai')
            ->setParameter('uai', $uai)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneBySiren(string $siren): ?GroupingClasses
    {
        return $this->createQueryBuilder('gc')
            ->where('gc.siren = :siren')
            ->setParameter('siren', $siren)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return GroupingClasses[] Returns an array of GroupingClasses objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?GroupingClasses
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
