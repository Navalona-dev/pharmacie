<?php

namespace App\Entity;

use App\Repository\RevenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RevenuRepository::class)]
class Revenu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $designation = null;

    /**
     * @var Collection<int, Facture>
     */
    #[ORM\OneToMany(targetEntity: Facture::class, mappedBy: 'Revenu')]
    private Collection $factures;

    #[ORM\Column(nullable: true)]
    private ?float $espece = null;

    #[ORM\Column(nullable: true)]
    private ?float $mobileMoney = null;

    #[ORM\Column(nullable: true)]
    private ?float $total = null;

    #[ORM\ManyToOne(inversedBy: 'Revenus')]
    private ?Application $application = null;

    /**
     * @var Collection<int, FactureRevenu>
     */
    #[ORM\OneToMany(targetEntity: FactureRevenu::class, mappedBy: 'Revenu')]
    private Collection $factureRevenus;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateRevenu = null;

    /**
     * @var Collection<int, Comptabilite>
     */
    #[ORM\ManyToMany(targetEntity: Comptabilite::class, mappedBy: 'Revenus')]
    private Collection $comptabilites;

    #[ORM\ManyToOne(inversedBy: 'revenus')]
    private ?Session $session = null;

    public function __construct()
    {
        $this->factures = new ArrayCollection();
        $this->factureRevenus = new ArrayCollection();
        $this->comptabilites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(?string $designation): static
    {
        $this->designation = $designation;

        return $this;
    }

    /**
     * @return Collection<int, Facture>
     */
    public function getFactures(): Collection
    {
        return $this->factures;
    }

    public function addFacture(Facture $facture): static
    {
        if (!$this->factures->contains($facture)) {
            $this->factures->add($facture);
            $facture->setRevenu($this);
        }

        return $this;
    }

    public function removeFacture(Facture $facture): static
    {
        if ($this->factures->removeElement($facture)) {
            // set the owning side to null (unless already changed)
            if ($facture->getRevenu() === $this) {
                $facture->setRevenu(null);
            }
        }

        return $this;
    }

    public function getEspece(): ?float
    {
        return $this->espece;
    }

    public function setEspece(?float $espece): static
    {
        $this->espece = $espece;

        return $this;
    }

    public function getMobileMoney(): ?float
    {
        return $this->mobileMoney;
    }

    public function setMobileMoney(?float $mobileMoney): static
    {
        $this->mobileMoney = $mobileMoney;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(?float $total): static
    {
        $this->total = $total;

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
     * @return Collection<int, FactureRevenu>
     */
    public function getFactureRevenus(): Collection
    {
        return $this->factureRevenus;
    }

    public function addFactureRevenu(FactureRevenu $factureRevenu): static
    {
        if (!$this->factureRevenus->contains($factureRevenu)) {
            $this->factureRevenus->add($factureRevenu);
            $factureRevenu->setRevenu($this);
        }

        return $this;
    }

    public function removeFactureRevenu(FactureRevenu $factureRevenu): static
    {
        if ($this->factureRevenus->removeElement($factureRevenu)) {
            // set the owning side to null (unless already changed)
            if ($factureRevenu->getRevenu() === $this) {
                $factureRevenu->setRevenu(null);
            }
        }

        return $this;
    }

    public function getDateRevenu(): ?\DateTimeInterface
    {
        return $this->dateRevenu;
    }

    public function setDateRevenu(?\DateTimeInterface $dateRevenu): static
    {
        $this->dateRevenu = $dateRevenu;

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
            $comptabilite->addRevenu($this);
        }

        return $this;
    }

    public function removeComptabilite(Comptabilite $comptabilite): static
    {
        if ($this->comptabilites->removeElement($comptabilite)) {
            $comptabilite->removeRevenu($this);
        }

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): static
    {
        $this->session = $session;

        return $this;
    }
}
