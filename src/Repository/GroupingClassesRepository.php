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

    /**
     * Permet de récupérer un établissement à partir de son uai
     *
     * @param string $uai L'uai de l'établissement
     * @return GroupingClasses|null L'établissement s'il existe
     */
    public function findOneByUai(string $uai): ?GroupingClasses
    {
        return $this->findOneBy(['uai' => $uai]);
    }

    /**
     * Permet de savoir si un enseignant est déjà enregistré dans un établissement
     *
     * @param GroupingClasses $groupingClasses L'établissement
     * @param User $teacher L'enseignant
     * @return boolean true si il est enregistré, false sinon
     */
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

    /**
     * Permet de récupérer un établissement à partir de son siren
     *
     * @param string $siren Le siren
     * @return GroupingClasses|null L'établissement s'il existe
     */
    public function findOneBySiren(string $siren): ?GroupingClasses
    {
        return $this->findOneBy(['siren' => $siren]);
    }

    /**
     * Permet de récupérer les identifiants de groupingClasses dans lesquels
     * est présent l'enseignant
     *
     * @param User $teacher
     * @return string[] La liste des id de groupingClasses
     */
    public function findIdWimsGroupingClassesByTeacher(User $teacher): array
    {
        
        $qb = $this->createQueryBuilder('gc');
        $result = $qb->select('gc.idWims')
            ->innerJoin('gc.registeredTeachers', 'u')
            ->where($qb->expr()->eq('u', ':teacher'))
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getSingleColumnResult();
        return $result;
    }
}
