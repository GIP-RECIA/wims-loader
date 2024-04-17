<?php

namespace App\Entity;

use App\Repository\GroupingClassesRepository;
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

    #[ORM\Column(length: 50)]
    private ?string $name = null;

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
}
