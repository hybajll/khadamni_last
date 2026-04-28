<?php

namespace App\Controller;

use App\Entity\Society;
use App\Service\SocietySubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SOCIETY')]
#[Route('/society/payment')]
final class SocietyPaymentController extends AbstractController
{
    #[Route('/start', name: 'society_payment_start', methods: ['GET', 'POST'])]
    public function start(Request $request, SocietySubscriptionService $subscriptionService): Response
    {
        /** @var Society $society */
        $society = $this->getUser();
        if (!$society) {
            return $this->redirectToRoute('society_login');
        }

        if ($subscriptionService->isSubscribed($society)) {
            $this->addFlash('info', "Votre abonnement est déjà actif.");
            return $this->redirectToRoute('society_subscription');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('society_payment_start', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('society_payment_start');
            }

            // Academic / simulated payment: activate subscription immediately.
            $subscriptionService->activateSubscription($society);

            $this->addFlash('success', 'Paiement simulé confirmé. Abonnement actif.');
            return $this->redirectToRoute('society_subscription');
        }

        return $this->render('society/payment/start.html.twig', [
            'price' => SocietySubscriptionService::MONTHLY_PRICE_TND,
            'currency' => 'TND',
        ]);
    }
}

