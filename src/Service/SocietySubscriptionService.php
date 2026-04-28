<?php

namespace App\Service;

use App\Entity\Society;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Subscription logic for societies (publish offers).
 * Academic version: subscription activation is handled by a simulated payment form.
 */
final class SocietySubscriptionService
{
    public const FREE_PUBLICATIONS_LIMIT = 1;
    public const MONTHLY_PRICE_TND = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function refreshExpiration(Society $society, ?\DateTimeImmutable $now = null): void
    {
        $now = $now ?? new \DateTimeImmutable();

        if ($society->getSubscriptionEndDate() instanceof \DateTimeImmutable && $society->getSubscriptionEndDate() <= $now) {
            $society->setSubscriptionEndDate(null);
            $this->entityManager->flush();
        }
    }

    public function isSubscribed(Society $society, ?\DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new \DateTimeImmutable();
        return $society->isSubscriptionActive($now);
    }

    public function remainingFreePublications(Society $society): int
    {
        return max(0, self::FREE_PUBLICATIONS_LIMIT - $society->getFreeUsageCount());
    }

    /**
     * Call before publishing an offer.
     * Returns null if allowed, otherwise returns a user-friendly error message.
     */
    public function blockMessageIfNotAllowed(Society $society): ?string
    {
        $this->refreshExpiration($society);

        if ($this->isSubscribed($society)) {
            return null;
        }

        if ($society->getFreeUsageCount() >= self::FREE_PUBLICATIONS_LIMIT) {
            return 'Vous avez utilisé votre publication gratuite. Veuillez vous abonner pour continuer.';
        }

        return null;
    }

    /**
     * Increment free usage count after a successful publication (only if not subscribed).
     */
    public function recordPublication(Society $society): void
    {
        $this->refreshExpiration($society);

        if ($this->isSubscribed($society)) {
            return;
        }

        $society->incrementFreeUsageCount();
        $this->entityManager->flush();
    }

    public function activateSubscription(Society $society): void
    {
        $now = new \DateTimeImmutable();

        $start = $now;
        if ($society->getSubscriptionEndDate() instanceof \DateTimeImmutable && $society->getSubscriptionEndDate() > $now) {
            $start = $society->getSubscriptionEndDate();
        }

        $end = $start->add(new \DateInterval('P1M'));
        $society->setSubscriptionEndDate($end);

        $this->entityManager->flush();
    }
}
