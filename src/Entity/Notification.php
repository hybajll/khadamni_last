<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
class Notification
{
    // Types de notifications
    public const TYPE_PAYMENT_CONFIRMATION = 'payment_confirmation';
    public const TYPE_PROFILE_VIEWED       = 'profile_viewed';
    public const TYPE_NEW_OFFER            = 'new_offer';
    public const TYPE_NEW_INTERNSHIP       = 'new_internship';
    public const TYPE_TECH_NEWS            = 'tech_news';
    public const TYPE_SUBSCRIPTION_EXPIRY = 'subscription_expiry';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Le User (Etudiant/Diplome) destinataire de la notification.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Type de notification : payment_confirmation | profile_viewed | new_offer | new_internship | tech_news
     */
    #[ORM\Column(length: 50)]
    private string $type = '';

    /**
     * Titre court affiché dans la cloche.
     */
    #[ORM\Column(length: 255)]
    private string $title = '';

    /**
     * Message détaillé de la notification.
     */
    #[ORM\Column(type: 'text')]
    private string $message = '';

    /**
     * URL vers laquelle redirige la notification (optionnel).
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $link = null;

    /**
     * La notification a-t-elle été lue ?
     */
    #[ORM\Column(name: 'is_read', type: 'boolean')]
    private bool $isRead = false;

    /**
     * Date de création.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ─── Getters / Setters ────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function markAsRead(): self
    {
        $this->isRead = true;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Retourne l'icône Bootstrap Icons selon le type.
     */
    public function getIcon(): string
    {
        return match ($this->type) {
            self::TYPE_PAYMENT_CONFIRMATION => 'bi-check-circle-fill text-success',
            self::TYPE_PROFILE_VIEWED       => 'bi-eye-fill text-primary',
            self::TYPE_NEW_OFFER            => 'bi-briefcase-fill text-warning',
            self::TYPE_NEW_INTERNSHIP       => 'bi-mortarboard-fill text-info',
            self::TYPE_TECH_NEWS            => 'bi-newspaper text-secondary',
            self::TYPE_SUBSCRIPTION_EXPIRY  => 'bi-alarm-fill text-danger',
            default                          => 'bi-bell-fill text-muted',
        };
    }
}
