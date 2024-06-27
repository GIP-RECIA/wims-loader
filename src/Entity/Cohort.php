<?php

namespace App\Entity;

use App\Enum\CohortType;
use App\Repository\CohortRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: CohortRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_ID', fields: ['id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_GROUPINGCLASSES_NAME', fields: ['GroupingClasses', 'name', 'teacher'])]
class Cohort
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?GroupingClasses $groupingClasses = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $teacher = null;

    #[ORM\Column]
    private ?int $idWims = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $lastSyncAt = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'cohort')]
    private Collection $students;

    /**
     * La ou les matières de cette cohorte
     *
     * @var string|null
     */
    #[ORM\Column(length: 255)]
    private ?string $subjects = null;

    #[ORM\Column]
    private ?CohortType $type = null;

    public function __construct()
    {
        $this->students = new ArrayCollection();
    }

    public function __toString()
    {
        return (string) $this->name . ' (' . $this->id . ', '
            . $this->getFullIdWims() . ', ' . $this->getTypeString() . ', '
            . $this->getName() . ')';
    }

    public function getFullIdWims(): string
    {
        return $this->groupingClasses->getIdWims() . '/' . $this->idWims;
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

    public function getGroupingClasses(): ?GroupingClasses
    {
        return $this->groupingClasses;
    }

    public function setGroupingClasses(?GroupingClasses $groupingClasses): static
    {
        $this->groupingClasses = $groupingClasses;

        return $this;
    }

    public function getTeacher(): ?User
    {
        return $this->teacher;
    }

    public function setTeacher(?User $teacher): static
    {
        $this->teacher = $teacher;

        return $this;
    }

    public function getIdWims(): ?int
    {
        return $this->idWims;
    }

    public function setIdWims(int $idWims): static
    {
        $this->idWims = $idWims;

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

    public function getLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(): static
    {
        $this->lastSyncAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(User $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
        }

        return $this;
    }

    public function removeStudent(User $student): static
    {
        $this->students->removeElement($student);

        return $this;
    }

    public function getSubjects(): ?string
    {
        return $this->subjects;
    }

    public function setSubjects(string $subjects): static
    {
        $this->subjects = $subjects;

        return $this;
    }

    public function getType(): ?CohortType
    {
        return $this->type;
    }

    public function setType(CohortType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeString(): ?string
    {
        return Cohort::cohortTypeString($this->type);
    }

    static public function cohortTypeString(CohortType $type): ?string
    {
        if ($type === CohortType::TYPE_CLASS) {
            return "class";
        } else if ($type === CohortType::TYPE_GROUP) {
            return "group";
        }

        return null;
    }
}
