<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
#[ORM\Table(name: "offers")]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Society::class, inversedBy: 'offres')]
    #[ORM\JoinColumn(name: "society_id", referencedColumnName: "id", nullable: false)]
    private ?Society $society = null;

    #[ORM\OneToMany(mappedBy: 'offre', targetEntity: Candidature::class)]
    private Collection $candidatures;

    public function __construct() { $this->candidatures = new ArrayCollection(); }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getSociety(): ?Society { return $this->society; }
    public function setSociety(?Society $society): static { $this->society = $society; return $this; }
    public function getCandidatures(): Collection { return $this->candidatures; }
}