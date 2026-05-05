<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Offer;
use App\Entity\Payment;
use App\Entity\Society;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service central pour créer les notifications in-app (cloche navbar).
 *
 * Usage :
 *   $inAppNotificationService->create($user, 'type', 'Titre', 'Message', '/lien');
 *   $inAppNotificationService->notifyPaymentConfirmed($payment);
 *   $inAppNotificationService->notifyProfileViewed($user, $society);
 *   $inAppNotificationService->notifyNewMatchingOffer($user, $offer);
 */
class InAppNotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationRepository $notificationRepository,
    ) {}

    // ─── Création générique ───────────────────────────────────────────────

    public function create(
        User    $user,
        string  $type,
        string  $title,
        string  $message,
        ?string $link = null,
    ): Notification {
        $notification = new Notification();
        $notification
            ->setUser($user)
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setLink($link);

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }

    // ─── 1. Confirmation de paiement ──────────────────────────────────────

    public function notifyPaymentConfirmed(Payment $payment): Notification
    {
        $user     = $payment->getUser();
        $amount   = $payment->getAmount();
        $currency = $payment->getCurrency();

        return $this->create(
            user: $user,
            type: Notification::TYPE_PAYMENT_CONFIRMATION,
            title: '✅ Paiement confirmé',
            message: sprintf(
                'Votre paiement de %s %s a été confirmé avec succès. Votre abonnement est maintenant actif.',
                $amount,
                $currency
            ),
            link: '/subscription',
        );
    }

    // ─── 2. Société a consulté votre profil ───────────────────────────────

    public function notifyProfileViewed(User $user, Society $society): Notification
    {
        return $this->create(
            user: $user,
            type: Notification::TYPE_PROFILE_VIEWED,
            title: '👁️ Profil consulté',
            message: sprintf(
                'La société "%s" a consulté votre profil. Assurez-vous que votre CV est à jour !',
                $society->getName() ?? $society->getEmail()
            ),
            link: '/user/profile',
        );
    }

    // ─── 3. Nouvelle offre correspondant au profil ─────────────────────────

    public function notifyNewMatchingOffer(User $user, Offer $offer): Notification
    {
        return $this->create(
            user: $user,
            type: Notification::TYPE_NEW_OFFER,
            title: '💼 Nouvelle offre pour vous',
            message: sprintf(
                '"%s" correspond à votre profil. Postulez avant qu\'il ne soit trop tard !',
                $offer->getTitle()
            ),
            link: '/offers/' . $offer->getId(),
        );
    }

    // ─── 4. Rappel expiration abonnement ──────────────────────────────────

    public function notifySubscriptionExpiring(User $user, \DateTimeImmutable $endDate): Notification
    {
        return $this->create(
            user: $user,
            type: Notification::TYPE_SUBSCRIPTION_EXPIRY,
            title: '⚠️ Abonnement expirant bientôt',
            message: sprintf(
                'Votre abonnement Khadamni expire le %s (dans 2 jours). Renouvelez dès maintenant pour ne pas perdre accès à vos fonctionnalités.',
                $endDate->format('d/m/Y')
            ),
            link: '/subscription',
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function markAllRead(User $user): void
    {
        $this->notificationRepository->markAllReadForUser($user);
    }

    public function countUnread(User $user): int
    {
        return $this->notificationRepository->countUnreadByUser($user);
    }
}
