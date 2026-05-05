<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class SubscriptionService
{
    public const FREE_ACTIONS_LIMIT = 2;
    public const MONTHLY_PRICE_TND = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function refreshExpiration(User $user, ?\DateTimeImmutable $now = null): void
    {
        $now = $now ?? new \DateTimeImmutable();

        if ($user->getSubscriptionEndDate() instanceof \DateTimeImmutable && $user->getSubscriptionEndDate() <= $now) {
            $user->setSubscriptionEndDate(null);
            $this->entityManager->flush();
        }
    }

    public function isSubscribed(User $user, ?\DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new \DateTimeImmutable();
        return $user->isSubscriptionActive($now);
    }

    public function remainingFreeActions(User $user): int
    {
        return max(0, self::FREE_ACTIONS_LIMIT - $user->getFreeUsageCount());
    }

    /**
     * Call before performing a paid action (apply / CV improve).
     * Returns null if allowed, otherwise returns a user-friendly error message.
     */
    public function blockMessageIfNotAllowed(User $user): ?string
    {
        $this->refreshExpiration($user);

        if ($this->isSubscribed($user)) {
            return null;
        }

        if ($user->getFreeUsageCount() >= self::FREE_ACTIONS_LIMIT) {
            return 'You have used your 2 free actions. Please subscribe to continue.';
        }

        return null;
    }

    /**
     * Increment free usage count after a successful action (only if not subscribed).
     */
    public function recordAction(User $user): void
    {
        $this->refreshExpiration($user);

        if ($this->isSubscribed($user)) {
            return;
        }

        $user->incrementFreeUsageCount();
        $this->entityManager->flush();
    }

    public function createPendingPayment(User $user): Payment
    {
        return $this->createPendingPaymentWithMethod($user, null);
    }

    public function createPendingPaymentWithMethod(User $user, ?string $method): Payment
    {
        $method = $method ? strtolower(trim($method)) : null;
        $methodSlug = $method ? preg_replace('/[^a-z0-9_\\-]/', '', $method) : null;

        $payment = new Payment();
        $payment
            ->setUser($user)
            ->setAmount(self::MONTHLY_PRICE_TND)
            ->setCurrency('TND')
            ->setStatus(Payment::STATUS_PENDING)
            ->setProviderRef(($methodSlug ? 'FAKE-'.$methodSlug.'-' : 'FAKE-').bin2hex(random_bytes(6)));

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    public function confirmPayment(Payment $payment): void
    {
        if ($payment->getStatus() === Payment::STATUS_PAID) {
            return;
        }

        $now = new \DateTimeImmutable();
        $user = $payment->getUser();

        $start = $now;
        if ($user->getSubscriptionEndDate() instanceof \DateTimeImmutable && $user->getSubscriptionEndDate() > $now) {
            $start = $user->getSubscriptionEndDate();
        }

        $end = $start->add(new \DateInterval('P1M'));

        $subscription = new Subscription();
        $subscription
            ->setUser($user)
            ->setStartAt($start)
            ->setEndAt($end)
            ->setCurrency('TND')
            ->setAmount(self::MONTHLY_PRICE_TND)
            ->setStatus(Subscription::STATUS_ACTIVE);

        $payment
            ->setStatus(Payment::STATUS_PAID)
            ->setSubscription($subscription);

        $user->setSubscriptionEndDate($end);

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }
}
