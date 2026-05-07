<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Recommendation;
use App\Form\CandidatureType;
use App\Repository\CvRepository;
use App\Repository\OfferRepository;
use App\Service\CvPdfGenerator;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CandidatureController extends AbstractController
{
    #[Route('/postuler', name: 'app_candidature_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        OfferRepository $offerRepository,
        CvRepository $cvRepository,
        CvPdfGenerator $cvPdfGenerator,
        SubscriptionService $subscriptionService,
    ): Response {
        $candidature = new Candidature();

        $offreDisabled = false;
        $offerId = $request->query->getInt('offer', 0);
        if ($offerId > 0) {
            $offer = $offerRepository->find($offerId);
            if ($offer) {
                $candidature->setOffre($offer);
                $offreDisabled = true;
            }
        }

        $form = $this->createForm(CandidatureType::class, $candidature, [
            'offre_disabled' => $offreDisabled,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof \App\Entity\User) {
                $block = $subscriptionService->blockMessageIfNotAllowed($user);
                if ($block) {
                    $this->addFlash('error', $block);
                    $this->addFlash('info', 'Actions gratuites restantes : '.$subscriptionService->remainingFreeActions($user));
                    return $this->redirectToRoute('app_subscription');
                }
            }

            // Default email to logged-in user (avoid manual mismatch)
            if ($user instanceof \App\Entity\User) {
                $candidature->setEmail((string) $user->getEmail());
            }

            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cvs';
            if (!is_dir($uploadsDir)) {
                @mkdir($uploadsDir, 0775, true);
            }

            // CV PDF: upload is optional. If missing, use the user's saved CV (and generate/copy a PDF).
            $cvFile = $form->get('cvPath')->getData();
            if ($cvFile) {
                $newFilename = 'cv-' . uniqid() . '.' . ($cvFile->guessExtension() ?: 'pdf');
                try {
                    $cvFile->move($uploadsDir, $newFilename);
                    $candidature->setCvPath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', "Une erreur est survenue lors de l'upload du CV.");
                    return $this->redirectToRoute('app_candidature_new', ['offer' => $offerId ?: null]);
                }
            } else {
                if (!$user instanceof \App\Entity\User) {
                    $this->addFlash('danger', "Vous devez être connecté.");
                    return $this->redirectToRoute('app_login');
                }

                $cv = $cvRepository->findOneBy(['user' => $user]);
                if (!$cv) {
                    $this->addFlash('danger', "Ajoutez votre CV avant de postuler.");
                    return $this->redirectToRoute('app_cv_manage');
                }

                $newFilename = 'cv-' . $user->getId() . '-' . uniqid() . '.pdf';

                // If the user imported a PDF, copy it. Otherwise generate the PDF from saved content.
                $publicRoot = $this->getParameter('kernel.project_dir') . '/public/';
                $importedPath = $cv->getPdfPath();
                $importedAbsolute = $importedPath ? $publicRoot . ltrim($importedPath, '/\\') : null;

                if ($importedAbsolute && is_file($importedAbsolute) && is_readable($importedAbsolute)) {
                    @copy($importedAbsolute, $uploadsDir . '/' . $newFilename);
                    $candidature->setCvPath($newFilename);
                } else {
                    $pdfResponse = $cvPdfGenerator->downloadResponse($cv);
                    $bytes = $pdfResponse->getContent();
                    if (is_string($bytes) && $bytes !== '') {
                        @file_put_contents($uploadsDir . '/' . $newFilename, $bytes);
                        $candidature->setCvPath($newFilename);
                    } else {
                        $this->addFlash('danger', "Impossible de générer le PDF de votre CV. Réessayez.");
                        return $this->redirectToRoute('app_cv_manage');
                    }
                }
            }

            if (!$candidature->getCvPath()) {
                $this->addFlash('danger', "Veuillez joindre un CV (PDF) ou créer votre CV dans l'application.");
                return $this->redirectToRoute('app_candidature_new', ['offer' => $offerId ?: null]);
            }

            $recommendation = new Recommendation();
            $recommendation->setScore(mt_rand(50, 95) / 100);
            $recommendation->setCandidature($candidature);

            $entityManager->persist($candidature);
            $entityManager->persist($recommendation);
            $entityManager->flush();

            if ($user instanceof \App\Entity\User) {
                $subscriptionService->recordAction($user);
            }

            $this->addFlash('success', 'Votre candidature a été transmise avec succès !');
            return $this->redirectToRoute('app_candidature_success');
        }

        return $this->render('front/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/offres/{id}/candidatures', name: 'app_admin_offre_candidatures', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminScores(int $id, EntityManagerInterface $em): Response
    {
        $offre = $em->getRepository(\App\Entity\Offer::class)->find($id);
        if (!$offre) {
            throw $this->createNotFoundException('Offre introuvable');
        }

        $candidatures = $em->getRepository(\App\Entity\Candidature::class)->findBy(['offre' => $offre]);

        usort($candidatures, function ($a, $b) {
            $scoreA = $a->getRecommendation() ? $a->getRecommendation()->getScore() : 0;
            $scoreB = $b->getRecommendation() ? $b->getRecommendation()->getScore() : 0;
            return $scoreB <=> $scoreA;
        });

        return $this->render('admin/scores.html.twig', [
            'offre' => $offre,
            'candidatures' => $candidatures,
        ]);
    }

    #[Route('/success', name: 'app_candidature_success', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function success(): Response
    {
        return $this->render('front/success.html.twig');
    }
}
