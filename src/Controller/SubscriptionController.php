<?php

namespace App\Controller;

use App\Service\SubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/subscription')]
final class SubscriptionController extends AbstractController
{
    #[Route('', name: 'app_subscription', methods: ['GET'])]
    public function subscribe(SubscriptionService $subscriptionService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $subscriptionService->refreshExpiration($user);

        return $this->render('subscription/index.html.twig', [
            'remaining' => $subscriptionService->remainingFreeActions($user),
            'subscribed' => $subscriptionService->isSubscribed($user),
            'end_date' => $user->getSubscriptionEndDate(),
            'price' => SubscriptionService::MONTHLY_PRICE_TND,
            'currency' => 'TND',
        ]);
    }

    #[Route('/status', name: 'app_subscription_status', methods: ['GET'])]
    public function checkSubscriptionStatus(SubscriptionService $subscriptionService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['authenticated' => false]);
        }

        $subscriptionService->refreshExpiration($user);

        return new JsonResponse([
            'authenticated' => true,
            'subscribed' => $subscriptionService->isSubscribed($user),
            'remainingFreeActions' => $subscriptionService->remainingFreeActions($user),
            'subscriptionEndDate' => $user->getSubscriptionEndDate()?->format('c'),
        ]);
    }
}

