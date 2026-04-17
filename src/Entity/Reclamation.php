<?php

namespace App\Entity;

use App\Enum\StatutReclamation;
use App\Enum\TypeReclamation;
use App\Repository\ReclamationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
#[ORM\Table(name: 'reclamation')]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_reclamation = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $sujet = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_modification = null;

    #[ORM\Column(type: 'string', enumType: StatutReclamation::class, nullable: false)]
    private ?StatutReclamation $statut = StatutReclamation::EN_ATTENTE;

    #[ORM\Column(type: 'string', enumType: TypeReclamation::class, nullable: false)]
    private ?TypeReclamation $type = TypeReclamation::PLATEFORME;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reclamations')]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: ReponseReclamation::class, mappedBy: 'reclamation', cascade: ['persist', 'remove'])]
    private Collection $reponseReclamations;

    public function __construct()
    {
        $this->reponseReclamations = new ArrayCollection();
        $this->date_creation = new \DateTime();
        $this->statut = StatutReclamation::EN_ATTENTE;
    }

    public function getIdReclamation(): ?int
    {
        return $this->id_reclamation;
    }

    public function setIdReclamation(int $id_reclamation): self
    {
        $this->id_reclamation = $id_reclamation;
        return $this;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(string $sujet): self
    {
        $this->sujet = $sujet;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->date_modification;
    }

    public function setDateModification(?\DateTimeInterface $date_modification): self
    {
        $this->date_modification = $date_modification;
        return $this;
    }

    public function getStatut(): ?StatutReclamation
    {
        return $this->statut;
    }

    public function setStatut(StatutReclamation $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getType(): ?TypeReclamation
    {
        return $this->type;
    }

    public function setType(TypeReclamation $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, ReponseReclamation>
     */
    public function getReponseReclamations(): Collection
    {
        return $this->reponseReclamations;
    }

    public function addReponseReclamation(ReponseReclamation $reponseReclamation): self
    {
        if (!$this->reponseReclamations->contains($reponseReclamation)) {
            $this->reponseReclamations->add($reponseReclamation);
            $reponseReclamation->setReclamation($this);
        }
        return $this;
    }

    public function removeReponseReclamation(ReponseReclamation $reponseReclamation): self
    {
        if ($this->reponseReclamations->removeElement($reponseReclamation)) {
            if ($reponseReclamation->getReclamation() === $this) {
                $reponseReclamation->setReclamation(null);
            }
        }
        return $this;
    }

    public function getStatutLabel(): string
    {
        return $this->statut ? $this->statut->getLabel() : '';
    }

    public function getStatutBadgeClass(): string
    {
        return $this->statut ? $this->statut->getBadgeClass() : '';
    }

    public function getTypeLabel(): string
    {
        return $this->type ? $this->type->getLabel() : '';
    }
}