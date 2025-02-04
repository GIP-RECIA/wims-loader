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

use App\Repository\GroupingClassesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupingClassesRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_ID', fields: ['id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_UAI', fields: ['uai'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_ID_WIMS', fields: ['idWims'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_SIREN', fields: ['siren'])]
class GroupingClasses
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 8)]
    private ?string $uai = null;

    #[ORM\Column(length: 7)]
    private ?string $idWims = null;

    #[ORM\Column(length: 15)]
    private ?string $siren = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'groupingClasses')]
    private Collection $registeredTeachers;

    public function __construct()
    {
        $this->registeredTeachers = new ArrayCollection();
    }

    public function __toString()
    {
        return (string) $this->name . ' (' .
            $this->id . ', ' . $this->uai . ', ' . $this->idWims . ')';
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

    public function getUai(): ?string
    {
        return $this->uai;
    }

    public function setUai(string $uai): static
    {
        $this->uai = $uai;

        return $this;
    }

    public function getIdWims(): ?string
    {
        return $this->idWims;
    }

    public function setIdWims(string $idWims): static
    {
        $this->idWims = $idWims;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getRegisteredTeachers(): Collection
    {
        return $this->registeredTeachers;
    }

    public function addRegisteredTeacher(User $registeredTeacher): static
    {
        if (!$this->registeredTeachers->contains($registeredTeacher)) {
            $this->registeredTeachers->add($registeredTeacher);
        }

        return $this;
    }

    public function removeRegisteredTeacher(User $registeredTeacher): static
    {
        $this->registeredTeachers->removeElement($registeredTeacher);

        return $this;
    }
}
