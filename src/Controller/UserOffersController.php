<?php

namespace App\Controller;

use App\Repository\CvRepository;
use App\Repository\OfferRepository;
use App\Service\CvJobMatchingAssistant;
use App\Service\SubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/user/offers')]
final class UserOffersController extends AbstractController
{
    #[Route('', name: 'app_user_offers', methods: ['GET'])]
    public function index(OfferRepository $offerRepository): Response
    {
        $offers = $offerRepository->findAllActive();

        return $this->render('offers/user_index.html.twig', [
            'offers' => $offers,
        ]);
    }

    #[Route('/recommended', name: 'app_user_offers_recommended', methods: ['GET'])]
    public function recommended(
        Request $request,
        OfferRepository $offerRepository,
        CvRepository $cvRepository,
        CvJobMatchingAssistant $matcher,
    ): Response {
        $user = $this->getUser();
        $cv = $cvRepository->findOneBy(['user' => $user]);

        if (!$cv) {
            $this->addFlash('error', 'Créez un CV avant de voir les offres compatibles.');
            return $this->redirectToRoute('app_cv_new');
        }

        $offers = $offerRepository->findAllActive();
        if ($offers === []) {
            $this->addFlash('info', 'Aucune offre active pour le moment.');
            return $this->redirectToRoute('app_user_offers');
        }

        $minScore = (int) $request->query->get('min', 60);
        $minScore = max(0, min(100, $minScore));

        $cvText = (string) $cv->getContenuOriginal();
        $results = $matcher->match($cvText, $offers);
        if ($results !== [] && isset($results[0]['reason']) && str_contains((string) $results[0]['reason'], 'hors IA')) {
            $this->addFlash('info', 'IA indisponible pour le moment. Matching simplifié (mots-clés) affiché.');
        }

        $recommended = array_values(array_filter($results, static fn (array $row): bool => (int) ($row['score'] ?? 0) >= $minScore));
        $recommended = array_slice($recommended, 0, 15);

        $showTopAnyway = false;
        if ($recommended === [] && $results !== []) {
            $showTopAnyway = true;
            $recommended = array_slice($results, 0, 10);
        }

        return $this->render('offers/recommended.html.twig', [
            'cv' => $cv,
            'min_score' => $minScore,
            'results' => $recommended,
            'show_top_anyway' => $showTopAnyway,
        ]);
    }

    #[Route('/{id}/apply', name: 'app_user_offers_apply', methods: ['POST'])]
    public function apply(
        Request $request,
        OfferRepository $offerRepository,
        SubscriptionService $subscriptionService,
        int $id,
    ): Response {
        $offer = $offerRepository->find($id);
        if (!$offer) {
            $this->addFlash('error', 'Offre introuvable.');
            return $this->redirectToRoute('app_user_offers');
        }

        if (!$this->isCsrfTokenValid('apply_offer_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_offer_public_show', ['id' => $id]);
        }

        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $block = $subscriptionService->blockMessageIfNotAllowed($user);
            if ($block) {
                $this->addFlash('error', $block);
                $this->addFlash('info', 'Actions gratuites restantes : '.$subscriptionService->remainingFreeActions($user));
                return $this->redirectToRoute('app_subscription');
            }
        }

        return $this->redirectToRoute('app_candidature_new', [
            'offer' => $offer->getId(),
        ]);
    }
}
