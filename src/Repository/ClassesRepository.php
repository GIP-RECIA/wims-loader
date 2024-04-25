<?php

namespace App\Repository;

use App\Entity\Classes;
use App\Entity\GroupingClasses;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Classes>
 *
 * @method Classes|null find($id, $lockMode = null, $lockVersion = null)
 * @method Classes|null findOneBy(array $criteria, array $orderBy = null)
 * @method Classes[]    findAll()
 * @method Classes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classes::class);
    }

    public function findOneByGroupingClassesTeacherAndName(GroupingClasses $groupingClasses, User $teacher, string $name): ?Classes
    {
        return $this->findOneBy([
            'groupingClasses' => $groupingClasses,
            'teacher' => $teacher,
            'name' => $name
        ]);
    }

    /**
     * Retourne la liste de toutes les classes auxquels l'élève est inscrit dans
     * l'établissement spécifié.
     *
     * @param GroupingClasses $groupingClasses L'établissement dans lequel rechercher les classes
     * @param User $student L'élève dont on cherche les classes
     * @return array La liste des classes de l'élève dans l'établissement courant
     */
    public function findByGroupingClassesAndStudent(GroupingClasses $groupingClasses, User $student): array
    {
        $res =  $this->createQueryBuilder('c')
            ->innerJoin('c.students', 's')
            ->where('c.groupingClasses = :groupingClasses')
            ->andWhere('s = :student')
            ->setParameter('groupingClasses', $groupingClasses)
            ->setParameters([
                'groupingClasses' => $groupingClasses,
                'student' => $student,
            ])
            ->getQuery();
            $res = $res
            ->getResult();

        return $res;
    }
}
