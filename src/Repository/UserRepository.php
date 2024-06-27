<?php
/**
 * Copyright © 2024 GIP-RECIA (https://www.recia.fr/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
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
