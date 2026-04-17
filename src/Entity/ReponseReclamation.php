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
     * Cette relation cible l'entité parente User. 
     * Doctrine utilisera l'ID de l'utilisateur (Admin ou Etudiant) 
     * pour remplir la colonne auteur_id.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'auteur_id', referencedColumnName: 'id', nullable: false)]
    private ?User $auteur = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_reponse = null;

    public function __construct()
    {
        $this->date_reponse = new \DateTime();
    }

    // --- Helpers Utiles pour le Chat ---

    /**
     * Permet de savoir facilement si l'auteur est un Admin 
     * (utile dans vos templates Twig pour le style des bulles de chat)
     */
    public function isAuteurAdmin(): bool
    {
        return $this->auteur instanceof Admin;
    }

    public function getTypeAuteur(): string
    {
        return $this->auteur ? $this->auteur->getType() : 'inconnu';
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