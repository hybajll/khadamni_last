<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\SmsLog;
use App\Entity\User;
use App\Repository\SmsLogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\InAppNotificationService;
use Psr\Log\LoggerInterface;

/**
 * Service principal du rappel d'expiration d'abonnement J-2.
 *
 * Pour chaque utilisateur dont l'abonnement expire dans exactement 2 jours :
 *   1. Envoie un SMS de rappel
 *   2. Crée une notification interne (cloche navbar)
 *   3. Enregistre le SMS dans sms_log pour éviter les doublons
 *
 * Déclenché par : php bin/console app:send-subscription-reminder
 */
class SubscriptionReminderService
{
    public function __construct(
        private readonly SmsService              $smsService,
        private readonly InAppNotificationService $notificationService,
        private readonly UserRepository          $userRepository,
        private readonly SmsLogRepository        $smsLogRepository,
        private readonly EntityManagerInterface  $em,
        private readonly LoggerInterface         $logger,
    ) {}

    /**
     * Traite tous les utilisateurs dont l'abonnement expire dans 2 jours.
     *
     * @return array{sent: int, skipped: int, failed: int}
     */
    public function processExpiringSubscriptions(): array
    {
        $targetDate = new \DateTimeImmutable('+2 days');
        $targetDate = $targetDate->setTime(0, 0, 0); // normaliser à minuit

        // Récupérer les users dont l'abonnement expire exactement dans 2 jours
        $users = $this->userRepository->findUsersWithSubscriptionExpiringOn($targetDate);

        $sent    = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($users as $user) {
            $endDate = \DateTimeImmutable::createFromMutable(
                $user->getSubscriptionEndDate()
            )->setTime(0, 0, 0);

            // ── Anti-doublon : SMS déjà envoyé pour cette date ? ──
            if ($this->smsLogRepository->hasAlreadySent(
                $user,
                SmsLog::TYPE_SUBSCRIPTION_EXPIRY,
                $endDate
            )) {
                $skipped++;
                $this->logger->info("SMS déjà envoyé pour {$user->getEmail()} / {$endDate->format('Y-m-d')}");
                continue;
            }

            // ── Vérifier que le user a un numéro de téléphone ──
            $phone = $user->getPhone();
            if (empty($phone)) {
                $this->logger->warning("Pas de téléphone pour {$user->getEmail()} — SMS ignoré");
                $skipped++;
                // On crée quand même la notification interne
                $this->createInAppNotification($user, $endDate);
                continue;
            }

            // ── Composer le message SMS ──
            $smsText = $this->buildSmsMessage($user, $endDate);

            // ── Envoyer le SMS ──
            $success = $this->smsService->send($phone, $smsText);

            // ── Enregistrer dans sms_log ──
            $this->saveSmsLog($user, $phone, $smsText, $endDate, $success);

            // ── Créer la notification interne (cloche) ──
            $this->createInAppNotification($user, $endDate);

            if ($success) {
                $sent++;
                $this->logger->info("✅ SMS envoyé à {$user->getEmail()} ({$phone})");
            } else {
                $failed++;
                $this->logger->error("❌ Échec SMS pour {$user->getEmail()} ({$phone})");
            }
        }

        $this->em->flush();

        return ['sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
    }

    // ─── Helpers privés ───────────────────────────────────────────────────

    /**
     * Compose le texte du SMS (max ~155 caractères pour éviter le split).
     */
    private function buildSmsMessage(User $user, \DateTimeImmutable $endDate): string
    {
        $prenom = $user->getPrenom() ?? 'Utilisateur';
        $date   = $endDate->format('d/m/Y');

        return sprintf(
            'Khadamni : Bonjour %s, votre abonnement expire le %s (dans 2 jours). Renouvelez sur khadamni.tn/subscription pour continuer à profiter de nos services.',
            $prenom,
            $date
        );
    }

    /**
     * Crée la notification interne dans la cloche (Notification entity).
     */
    private function createInAppNotification(User $user, \DateTimeImmutable $endDate): void
    {
        $date = $endDate->format('d/m/Y');

        // Ajouter le type dans Notification::class si absent
        $this->notificationService->create(
            user:    $user,
            type:    'subscription_expiry',   // TYPE_SUBSCRIPTION_EXPIRY à ajouter dans Notification.php
            title:   '⚠️ Abonnement expirant bientôt',
            message: sprintf(
                'Votre abonnement Khadamni expire le %s (dans 2 jours). Renouvelez dès maintenant pour ne pas perdre accès à vos offres et fonctionnalités.',
                $date
            ),
            link: '/subscription',
        );
    }

    /**
     * Sauvegarde la trace du SMS dans sms_log.
     */
    private function saveSmsLog(
        User               $user,
        string             $phone,
        string             $message,
        \DateTimeImmutable $endDate,
        bool               $success,
    ): void {
        $log = new SmsLog();
        $log->setUser($user)
            ->setType(SmsLog::TYPE_SUBSCRIPTION_EXPIRY)
            ->setPhoneNumber($phone)
            ->setMessage($message)
            ->setSubscriptionEndDate($endDate)
            ->setSuccess($success);

        $this->em->persist($log);
    }
}
