<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Subscription;
use App\Repository\PaymentRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MANAGER')]
#[Route('/admin/payments')]
final class AdminPaymentsController extends AbstractController
{
    #[Route('', name: 'app_admin_payments', methods: ['GET'])]
    public function index(
        Request $request,
        PaymentRepository $paymentRepository,
        SubscriptionRepository $subscriptionRepository,
    ): Response {
        $q = trim((string) $request->query->get('q', ''));

        $paymentsQb = $paymentRepository->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.createdAt', 'DESC');

        $subsQb = $subscriptionRepository->createQueryBuilder('s')
            ->leftJoin('s.user', 'u2')
            ->addSelect('u2')
            ->orderBy('s.endAt', 'DESC');

        if ($q !== '') {
            $paymentsQb->andWhere('u.email LIKE :q')->setParameter('q', '%'.$q.'%');
            $subsQb->andWhere('u2.email LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        return $this->render('admin/payments/index.html.twig', [
            'payments' => $paymentsQb->getQuery()->getResult(),
            'subscriptions' => $subsQb->getQuery()->getResult(),
            'q' => $q,
        ]);
    }

    #[Route('/payment/{id}/delete', name: 'app_admin_payment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPERADMIN')]
    public function deletePayment(
        Payment $payment,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_payment_'.$payment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_payments');
        }

        $entityManager->remove($payment);
        $entityManager->flush();

        $this->addFlash('success', 'Paiement supprimé.');
        return $this->redirectToRoute('app_admin_payments');
    }

    #[Route('/subscription/{id}/delete', name: 'app_admin_subscription_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPERADMIN')]
    public function deleteSubscription(
        Subscription $subscription,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_subscription_'.$subscription->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_payments');
        }

        $entityManager->remove($subscription);
        $entityManager->flush();

        $this->addFlash('success', 'Abonnement supprimé.');
        return $this->redirectToRoute('app_admin_payments');
    }
}
