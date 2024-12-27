<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'sessions')]
    private Collection $users;

    /**
     * @var Collection<int, Affaire>
     */
    #[ORM\OneToMany(targetEntity: Affaire::class, mappedBy: 'session')]
    private Collection $affaires;

    #[ORM\Column(nullable: true)]
    private ?bool $isClose = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    private ?Application $application = null;

    /**
     * @var Collection<int, Revenu>
     */
    #[ORM\OneToMany(targetEntity: Revenu::class, mappedBy: 'session')]
    private Collection $revenus;

    /**
     * @var Collection<int, Depense>
     */
    #[ORM\OneToMany(targetEntity: Depense::class, mappedBy: 'session')]
    private Collection $depenses;

    /**
     * @var Collection<int, Comptabilite>
     */
    #[ORM\OneToMany(targetEntity: Comptabilite::class, mappedBy: 'session')]
    private Collection $comptabilites;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->affaires = new ArrayCollection();
        $this->revenus = new ArrayCollection();
        $this->depenses = new ArrayCollection();
        $this->comptabilites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeInterface $heureDebut): static
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTimeInterface $heureFin): static
    {
        $this->heureFin = $heureFin;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * @return Collection<int, Affaire>
     */
    public function getAffaires(): Collection
    {
        return $this->affaires;
    }

    public function addAffaire(Affaire $affaire): static
    {
        if (!$this->affaires->contains($affaire)) {
            $this->affaires->add($affaire);
            $affaire->setSession($this);
        }

        return $this;
    }

    public function removeAffaire(Affaire $affaire): static
    {
        if ($this->affaires->removeElement($affaire)) {
            // set the owning side to null (unless already changed)
            if ($affaire->getSession() === $this) {
                $affaire->setSession(null);
            }
        }

        return $this;
    }

    public function isClose(): ?bool
    {
        return $this->isClose;
    }

    public function setClose(?bool $isClose): static
    {
        $this->isClose = $isClose;

        return $this;
    }

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): static
    {
        $this->application = $application;

        return $this;
    }

    /**
     * @return Collection<int, Revenu>
     */
    public function getRevenus(): Collection
    {
        return $this->revenus;
    }

    public function addRevenu(Revenu $revenu): static
    {
        if (!$this->revenus->contains($revenu)) {
            $this->revenus->add($revenu);
            $revenu->setSession($this);
        }

        return $this;
    }

    public function removeRevenu(Revenu $revenu): static
    {
        if ($this->revenus->removeElement($revenu)) {
            // set the owning side to null (unless already changed)
            if ($revenu->getSession() === $this) {
                $revenu->setSession(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Depense>
     */
    public function getDepenses(): Collection
    {
        return $this->depenses;
    }

    public function addDepense(Depense $depense): static
    {
        if (!$this->depenses->contains($depense)) {
            $this->depenses->add($depense);
            $depense->setSession($this);
        }

        return $this;
    }

    public function removeDepense(Depense $depense): static
    {
        if ($this->depenses->removeElement($depense)) {
            // set the owning side to null (unless already changed)
            if ($depense->getSession() === $this) {
                $depense->setSession(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comptabilite>
     */
    public function getComptabilites(): Collection
    {
        return $this->comptabilites;
    }

    public function addComptabilite(Comptabilite $comptabilite): static
    {
        if (!$this->comptabilites->contains($comptabilite)) {
            $this->comptabilites->add($comptabilite);
            $comptabilite->setSession($this);
        }

        return $this;
    }

    public function removeComptabilite(Comptabilite $comptabilite): static
    {
        if ($this->comptabilites->removeElement($comptabilite)) {
            // set the owning side to null (unless already changed)
            if ($comptabilite->getSession() === $this) {
                $comptabilite->setSession(null);
            }
        }

        return $this;
    }
}
