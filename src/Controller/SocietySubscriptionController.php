<?php

namespace App\Controller;

use App\Entity\Society;
use App\Service\SocietySubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SOCIETY')]
#[Route('/society/subscription')]
final class SocietySubscriptionController extends AbstractController
{
    #[Route('', name: 'society_subscription', methods: ['GET'])]
    public function index(SocietySubscriptionService $subscriptionService): Response
    {
        /** @var Society $society */
        $society = $this->getUser();
        if (!$society) {
            return $this->redirectToRoute('society_login');
        }

        $subscriptionService->refreshExpiration($society);

        return $this->render('society/subscription/index.html.twig', [
            'remaining' => $subscriptionService->remainingFreePublications($society),
            'subscribed' => $subscriptionService->isSubscribed($society),
            'end_date' => $society->getSubscriptionEndDate(),
            'price' => SocietySubscriptionService::MONTHLY_PRICE_TND,
            'currency' => 'TND',
        ]);
    }
}

