<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\SmsLog;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Envoie (optionnellement) un SMS et trace l'envoi en DB.
 *
 * Projet académique : si Twilio n'est pas configuré, SmsService::send() ne bloque pas.
 */
class PaymentSmsNotifier
{
    public function __construct(
        private readonly SmsService $smsService,
        private readonly EntityManagerInterface $em,
    ) {}

    public function notifyPaymentConfirmed(Payment $payment): void
    {
        $user = $payment->getUser();
        if (!$user) {
            return;
        }

        $phone = $user->getPhone();
        if (!$phone) {
            return;
        }

        $message = sprintf(
            'Khadamni: Votre paiement de %s %s a été confirmé. Merci !',
            $payment->getAmount(),
            $payment->getCurrency()
        );

        $success = $this->smsService->send($phone, $message);

        $log = new SmsLog();
        $log->setUser($user)
            ->setType(SmsLog::TYPE_PAYMENT_CONFIRMATION)
            ->setPhoneNumber($phone)
            ->setMessage($message)
            ->setSuccess($success);

        $this->em->persist($log);
        $this->em->flush();
    }
}

