<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "L'email est requis")]
    #[Assert\Email(message: "Format d'email invalide")]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $cvPath = null;

    #[ORM\ManyToOne(targetEntity: Offer::class)]
    #[ORM\JoinColumn(name: "offer_id", referencedColumnName: "id", nullable: false)]
    private ?Offer $offre = null;


#[ORM\OneToOne(mappedBy: 'candidature', targetEntity: Recommendation::class, cascade: ['persist', 'remove'])]
private ?Recommendation $recommendation = null; // Assure-toi que c'est bien écrit en minuscules ici// Dans src/Entity/Candidature.php


    public function getId(): ?int { return $this->id; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getCvPath(): ?string { return $this->cvPath; }
    public function setCvPath(string $cvPath): static { $this->cvPath = $cvPath; return $this; }
    public function getOffre(): ?Offer { return $this->offre; }
    public function setOffre(?Offer $offre): static { $this->offre = $offre; return $this; }
    public function getRecommendation(): ?Recommendation { return $this->recommendation; }
    public function setRecommendation(Recommendation $recommendation): static {
        if ($recommendation->getCandidature() !== $this) { $recommendation->setCandidature($this); }
        $this->recommendation = $recommendation;
        return $this;
    }
}      
