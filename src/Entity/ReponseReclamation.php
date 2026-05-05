<?php

namespace App\Entity;

use App\Repository\ReponseReclamationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseReclamationRepository::class)]
#[ORM\Table(name: 'reponse_reclamation')]
class ReponseReclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_reponse_reclamation = null;

    #[ORM\ManyToOne(targetEntity: Reclamation::class, inversedBy: 'reponseReclamations')]
    #[ORM\JoinColumn(name: 'id_reclamation', referencedColumnName: 'id_reclamation', nullable: false, onDelete: 'CASCADE')]
    private ?Reclamation $reclamation = null;

    /**
     * L'auteur peut être un User (Admin, Etudiant, etc.)
     * Si null, le message est considéré comme envoyé par l'IA.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'auteur_id', referencedColumnName: 'id', nullable: true)]
    private ?User $auteur = null;

    /**
     * L'auteur peut être une Society
     */
    #[ORM\ManyToOne(targetEntity: Society::class)]
    #[ORM\JoinColumn(name: 'society_auteur_id', referencedColumnName: 'id', nullable: true)]
    private ?Society $societyAuteur = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_reponse = null;

    public function __construct()
    {
        $this->date_reponse = new \DateTime();
    }

    /**
     * Utilisé par Twig pour déterminer le côté de la bulle de chat.
     * Un message est considéré "Admin/Support" s'il est écrit par un Admin
     * OU s'il est écrit par l'IA (auteur === null).
     */
    public function isAuteurAdmin(): bool
    {
        // CAS 1 : C'est l'Assistant IA (pas d'auteur en base)
        if ($this->auteur === null && $this->societyAuteur === null) {
            return true; 
        }

        // CAS 2 : C'est un utilisateur humain, on vérifie ses rôles
        if ($this->auteur !== null) {
            return in_array('ROLE_ADMIN', $this->auteur->getRoles());
        }

        return false;
    }

    /**
     * Retourne le nom de l'auteur pour l'affichage
     */
    public function getNomAffichage(): string
    {
        if ($this->societyAuteur) {
            return $this->societyAuteur->getName() ?? $this->societyAuteur->getEmail();
        }
        if ($this->auteur) {
            return $this->auteur->getNom() ?? $this->auteur->getEmail();
        }
        return "Assistant IA";
    }

    // --- Getters & Setters ---

    public function getIdReponseReclamation(): ?int
    {
        return $this->id_reponse_reclamation;
    }

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamation $reclamation): self
    {
        $this->reclamation = $reclamation;
        return $this;
    }

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?User $auteur): self
    {
        $this->auteur = $auteur;
        return $this;
    }

    public function getSocietyAuteur(): ?Society
    {
        return $this->societyAuteur;
    }

    public function setSocietyAuteur(?Society $societyAuteur): self
    {
        $this->societyAuteur = $societyAuteur;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getDateReponse(): ?\DateTimeInterface
    {
        return $this->date_reponse;
    }

    public function setDateReponse(\DateTimeInterface $date_reponse): self
    {
        $this->date_reponse = $date_reponse;
        return $this;
    }
}
