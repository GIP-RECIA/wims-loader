<?php

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
     * @var list<string> Les classes provenant du ticket cas
     */
    private array $ticketEnsClasses = [];

    /**
     * @var Collection<int, GroupingClasses>
     */
    #[ORM\ManyToMany(targetEntity: GroupingClasses::class, mappedBy: 'registeredTeachers')]
    private Collection $groupingClasses;

    public function __construct()
    {
        $this->groupingClasses = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
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
     * @return list<string>
     */
    public function getTicketEnsClasses(): array
    {
        return $this->ticketEnsClasses;
    }

    /**
     * @param list<string> $roles
     */
    public function setTicketEnsClasses(array $ticketEnsClasses): static
    {
        $this->ticketEnsClasses = $ticketEnsClasses;

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
}
