<?php

namespace App\Repository;

use App\Entity\Cohort;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Récupère un utilisateur par son uid
     *
     * @param string $uid L'uid recherché
     * @return User|null L'utilisateur s'il existe
     */
    public function findOneByUid(string $uid): ?User
    {
        return $this->findOneBy(['uid' => $uid]);
    }

    /**
     * Récupère un ensemble d'utilisateur par leur uid
     *
     * @param string[] $arrUid Les uid des utilisateurs recherchés
     * @return User[] Les utilisateurs trouvés
     */
    public function findByUid(array $arrUid): array
    {
        return $this->findBy(['uid' => $arrUid]);
    }

    /**
     * Récupérer par une cohorte les étudiants
     *
     * @param Cohort $cohort
     * @return array
     */
    public function findByCohort(Cohort $cohort): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.cohorts', 'c')
            ->where('c = :cohort')
            ->orderBy('u.lastName')
            ->addOrderBy('u.firstName')
            ->setParameter('cohort', $cohort)
            ->getQuery()
            ->getResult();
    }
}
