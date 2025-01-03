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
use App\Entity\GroupingClasses;
use App\Entity\User;
use App\Enum\CohortType;
use App\Service\CohortNameService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cohort>
 *
 * @method Cohort|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cohort|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cohort[]    findAll()
 * @method Cohort[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CohortRepository extends ServiceEntityRepository
{
    public function __construct(
        private ManagerRegistry $registry,
        private CohortNameService $cohortNameService,
        )
    {
        parent::__construct($registry, Cohort::class);
    }

    /**
     * Retourne la cohorte ayant le bon nom pour l'enseignant dans l'établissement
     *
     * @param GroupingClasses $groupingClasses L'établissement dans lequel rechercher la cohorte
     * @param User $teacher L'enseignant dont on cherche la cohorte
     * @return array La cohorte de l'enseignant dans l'établissement courant ou null
     */
    public function findOneByGroupingClassesTeacherAndName(GroupingClasses $groupingClasses, User $teacher, string $name): ?Cohort
    {
        return $this->findOneBy([
            'groupingClasses' => $groupingClasses,
            'teacher' => $teacher,
            'name' => $this->cohortNameService->generateName($name, $teacher)
        ]);
    }

    /**
     * Retourne la liste de toutes les cohortes dont on a passé les idWims en
     * paramètre, et leurs enseignants attitrés dans l'établissement spécifié.
     *
     * @param GroupingClasses $groupingClasses
     * @param string[] $idWimsCohorts Les idWims des cohortes
     * @return Cohort[] La liste des cohortes de l'élève dans l'établissement courant
     */
    public function findByIdWimsGroupingClassesAndIdWimsCohorts(GroupingClasses $groupingClasses, array $idWimsCohorts): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.teacher', 't')
            ->addSelect('t')
            ->where('c.groupingClasses = :groupingClasses')
            ->andWhere('c.idWims IN (:idWimsCohorts)')
            ->orderBy('c.type')
            ->setParameters([
                'groupingClasses' => $groupingClasses,
                'idWimsCohorts' => $idWimsCohorts
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne la liste de toutes les cohortes que possède l'enseignant dans
     * l'établissement spécifié et du type spécifié
     *
     * @param GroupingClasses $groupingClasses L'établissement dans lequel rechercher les cohortes
     * @param User $teacher L'enseignant dont on cherche les cohortes
     * @param CohortType $type Le type des cohortes recherchées, null pour toutes
     * @return array La liste des cohortes de l'enseignant dans l'établissement courant
     */
    public function findByGroupingClassesAndTeacher(GroupingClasses $groupingClasses, User $teacher, CohortType $type = null): array
    {
        $parameters = [
            'groupingClasses' => $groupingClasses,
            'teacher' => $teacher,
        ];

        $req = $this->createQueryBuilder('c')
            ->where('c.groupingClasses = :groupingClasses')
            ->andWhere('c.teacher = :teacher');
            

        if ($type !== null) {
            $parameters['type'] = $type;
            $req->andWhere('c.type = :type');
        }

        return $req->orderBy('c.name')
            ->setParameters($parameters)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne la liste de toutes les cohortes
     *
     * @return Cohort[] La liste de toutes les cohortes
     */
    public function findAllData(): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.groupingClasses', 'gc')
            ->innerJoin('c.teacher', 't')
            ->select('gc.name as gc_name, gc.uai as uai, c.name as c_name, t.lastName as lastName, t.firstName as firstName, c.subjects as subjects, CONCAT(gc.idWims, \'/\', c.idWims) as id_wims, c.type as type, c.id as id')
            ->groupBy('c.id')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Retourne la cohorte identifié par l'id wims complet
     *
     * @param string $fullIdWims L'id wims complet
     * @return mixed La cohort ou null
     */
    public function findCohortByFullIdWims(string $fullIdWims): ?Cohort
    {
        list($idGroupingClasses, $idCohort) = explode('/', $fullIdWims);
        $parameters = [
            'groupingClassesIdWims' => $idGroupingClasses,
            'cohortIdWims' => $idCohort,
        ];

        return $this->createQueryBuilder('c')
            ->innerJoin('c.groupingClasses', 'gc')
            ->where('gc.idWims = :groupingClassesIdWims')
            ->andWhere('c.idWims = :cohortIdWims')
            ->setParameters($parameters)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
