<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
#[Route('/admin/offers')]
final class AdminOffersController extends AbstractController
{
    #[Route('', name: 'app_admin_offers', methods: ['GET'])]
    public function index(Request $request, OfferRepository $offerRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));

        // Simple admin view (active only).
        $offers = $q !== ''
            ? $offerRepository->searchOffers($q, null, null)
            : $offerRepository->findAllActive();

        return $this->render('admin/offers/index.html.twig', [
            'offers' => $offers,
            'q' => $q,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_offer_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPERADMIN')]
    public function delete(int $id, Request $request, OfferRepository $offerRepository, EntityManagerInterface $entityManager): Response
    {
        $offer = $offerRepository->find($id);
        if (!$offer instanceof Offer) {
            $this->addFlash('error', 'Offre introuvable.');
            return $this->redirectToRoute('app_admin_offers');
        }

        if (!$this->isCsrfTokenValid('delete_offer_'.$offer->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_offers');
        }

        $entityManager->remove($offer);
        $entityManager->flush();

        $this->addFlash('success', 'Offre supprimée.');
        return $this->redirectToRoute('app_admin_offers');
    }
}

