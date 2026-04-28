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
     * L'auteur peut être un User (incluant Admin ou Etudiant via héritage)
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
     * Requis par Twig dans user/reclamation_support.html.twig
     * Vérifie si l'auteur du message est un administrateur
     */
    public function isAuteurAdmin(): bool
    {
        // On vérifie si l'auteur est un User et s'il possède le rôle ADMIN
        if ($this->auteur !== null) {
            // Si vous utilisez un héritage type "Admin extends User", instanceof fonctionne.
            // Sinon, on vérifie les rôles.
            return in_array('ROLE_ADMIN', $this->auteur->getRoles());
        }

        return false;
    }

    /**
     * Helper optionnel : Retourne le nom de l'auteur peu importe son type
     */
    public function getNomAffichage(): string
    {
        if ($this->societyAuteur) {
            return $this->societyAuteur->getNom(); // Remplacez par le getter réel de Society
        }
        if ($this->auteur) {
            return $this->auteur->getEmail(); // Ou getNom() / getPrenom()
        }
        return "Anonyme";
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