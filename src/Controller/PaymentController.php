<?php

namespace App\Controller;

use App\Service\InAppNotificationService;
use App\Service\PaymentSmsNotifier;
use App\Service\SubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/payment')]
final class PaymentController extends AbstractController
{
    #[Route('/start', name: 'app_payment_start', methods: ['GET', 'POST'])]
    public function start(
        Request $request,
        SubscriptionService $subscriptionService,
        InAppNotificationService $inAppNotificationService,
        PaymentSmsNotifier $paymentSmsNotifier,
    ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($subscriptionService->isSubscribed($user)) {
            $this->addFlash('info', "Votre abonnement est déjà actif.");
            return $this->redirectToRoute('app_subscription');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('payment_start', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_payment_start');
            }

            $method = (string) $request->request->get('method', 'card');

            $payment = $subscriptionService->createPendingPaymentWithMethod($user, $method);
            $subscriptionService->confirmPayment($payment);
            $inAppNotificationService->notifyPaymentConfirmed($payment);
            $paymentSmsNotifier->notifyPaymentConfirmed($payment);

            $this->addFlash('success', 'Paiement simulé confirmé. Abonnement actif.');
            return $this->redirectToRoute('app_subscription');
        }

        return $this->render('payment/start.html.twig', [
            'price' => SubscriptionService::MONTHLY_PRICE_TND,
            'currency' => 'TND',
        ]);
    }
}
