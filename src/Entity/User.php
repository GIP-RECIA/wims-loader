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
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_ID', fields: ['id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_UID', fields: ['uid'])]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var list<string> The user roles
     */
    private array $roles = [];

    #[ORM\Column(length: 8)]
    private ?string $uid = null;

    #[ORM\Column(length: 60)]
    private ?string $firstName = null;

    #[ORM\Column(length: 60)]
    private ?string $lastName = null;

    #[ORM\Column(length: 60)]
    private ?string $mail = null;

    private ?string $sirenCourant = null;

    /**
     * @var Collection<int, GroupingClasses>
     */
    #[ORM\ManyToMany(targetEntity: GroupingClasses::class, mappedBy: 'registeredTeachers')]
    private Collection $groupingClasses;

    /**
     * @var Collection<int, Cohort>
     */
    #[ORM\ManyToMany(targetEntity: Cohort::class, mappedBy: 'students')]
    private Collection $cohorts;

    public function __construct()
    {
        $this->groupingClasses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function __toString()
    {
        return (string) $this->firstName . ' ' . $this->lastName . ' (' .
            $this->id . ', ' . $this->uid . ')';
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(string $uid): static
    {
        $this->uid = $uid;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->firstName . " " . $this->lastName;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    public function getSirenCourant(): ?string
    {
        return $this->sirenCourant;
    }

    public function setSirenCourant(string $sirenCourant): static
    {
        $this->sirenCourant = $sirenCourant;

        return $this;
    }

    /**
     * @return Collection<int, GroupingClasses>
     */
    public function getGroupingClasses(): Collection
    {
        return $this->groupingClasses;
    }

    public function addGroupingClass(GroupingClasses $groupingClass): static
    {
        if (!$this->groupingClasses->contains($groupingClass)) {
            $this->groupingClasses->add($groupingClass);
            $groupingClass->addRegisteredTeacher($this);
        }

        return $this;
    }

    public function removeGroupingClass(GroupingClasses $groupingClass): static
    {
        if ($this->groupingClasses->removeElement($groupingClass)) {
            $groupingClass->removeRegisteredTeacher($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Cohort>
     */
    public function getCohorts(): Collection
    {
        return $this->cohorts;
    }

    public function addCohort(Cohort $cohort): static
    {
        if (!$this->cohorts->contains($cohort)) {
            $this->cohorts->add($cohort);
        }

        return $this;
    }

    public function removeCohort(Cohort $cohort): static
    {
        $this->cohorts->removeElement($cohort);

        return $this;
    }
}
