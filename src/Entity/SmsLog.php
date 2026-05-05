<?php

namespace App\Entity;

use App\Repository\SmsLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trace chaque SMS envoyé pour éviter les doublons
 * et avoir un historique complet.
 */
#[ORM\Entity(repositoryClass: SmsLogRepository::class)]
#[ORM\Table(name: 'sms_log')]
class SmsLog
{
    public const TYPE_SUBSCRIPTION_EXPIRY = 'subscription_expiry'; // rappel J-2
    public const TYPE_SUBSCRIPTION_EXPIRED = 'subscription_expired'; // expiré

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** Type du SMS envoyé */
    #[ORM\Column(length: 50)]
    private string $type = '';

    /** Numéro destinataire */
    #[ORM\Column(length: 20)]
    private string $phoneNumber = '';

    /** Texte du SMS */
    #[ORM\Column(type: 'text')]
    private string $message = '';

    /** SMS envoyé avec succès ? */
    #[ORM\Column(type: 'boolean')]
    private bool $success = false;

    /** Date d'expiration de l'abonnement concerné */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $subscriptionEndDate = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $sentAt;

    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    // ─── Getters / Setters ────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getPhoneNumber(): string { return $this->phoneNumber; }
    public function setPhoneNumber(string $phoneNumber): self { $this->phoneNumber = $phoneNumber; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }

    public function isSuccess(): bool { return $this->success; }
    public function setSuccess(bool $success): self { $this->success = $success; return $this; }

    public function getSubscriptionEndDate(): ?\DateTimeImmutable { return $this->subscriptionEndDate; }
    public function setSubscriptionEndDate(?\DateTimeImmutable $date): self { $this->subscriptionEndDate = $date; return $this; }

    public function getSentAt(): \DateTimeImmutable { return $this->sentAt; }
}
