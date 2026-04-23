<?php

namespace App\Entity;

use App\Repository\RecommendationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecommendationRepository::class)]
class Recommendation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $score = null;

    #[ORM\OneToOne(targetEntity: Candidature::class, inversedBy: 'recommendation')]
    #[ORM\JoinColumn(name: "candidature_id", referencedColumnName: "id", nullable: false)]
    private ?Candidature $candidature = null;

    public function getId(): ?int { return $this->id; }
    public function getScore(): ?float { return $this->score; }
    public function setScore(float $score): static { $this->score = $score; return $this; }
    public function getCandidature(): ?Candidature { return $this->candidature; }
    public function setCandidature(Candidature $candidature): static { $this->candidature = $candidature; return $this; }
}