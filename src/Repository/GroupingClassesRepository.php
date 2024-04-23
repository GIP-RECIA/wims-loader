<?php

namespace App\Repository;

use App\Entity\GroupingClasses;
use App\Entity\User;
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

    public function isTeacherRegistered(GroupingClasses $groupingClasses, User $teacher): bool
    {
        $qb = $this->createQueryBuilder('gc');
        $result = $qb->select('COUNT(gc)')
            ->innerJoin('gc.registeredTeachers', 'u')
            ->where($qb->expr()->eq('gc', ':groupingClasses'))
            ->andWhere($qb->expr()->eq('u', ':teacher'))
            ->setParameters([
                'groupingClasses' => $groupingClasses,
                'teacher' => $teacher,
            ])
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    public function findOneBySiren(string $siren): ?GroupingClasses
    {
        return $this->findOneBy(['siren' => $siren]);
    }
}
