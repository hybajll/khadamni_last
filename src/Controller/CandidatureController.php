<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Recommendation;
use App\Form\CandidatureType;
use App\Repository\CandidatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CandidatureController extends AbstractController
{
    /**
     * FRONT OFFICE : Formulaire pour postuler
     */
    #[Route('/postuler', name: 'app_candidature_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidature = new Candidature();
        $form = $this->createForm(CandidatureType::class, $candidature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 1. GESTION DE L'UPLOAD DU CV
            $cvFile = $form->get('cvPath')->getData();

            if ($cvFile) {
                // Création d'un nom de fichier unique pour éviter les doublons
                $newFilename = 'cv-'.uniqid().'.'.$cvFile->guessExtension();

                // Déplacement du fichier dans le dossier public/uploads/cvs
                try {
                    $cvFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/cvs',
                        $newFilename
                    );
                    
                    // On enregistre le NOM du fichier dans la base de données
                    $candidature->setCvPath($newFilename);
                    
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors de l\'upload du CV.');
                    return $this->redirectToRoute('app_candidature_new');
                }
            }

            // 2. SIMULATION DU SCORE IA (RECOMMENDATION)
            $recommendation = new Recommendation();
            $recommendation->setScore(mt_rand(50, 95) / 100);
            $recommendation->setCandidature($candidature);
            
            // 3. PERSISTANCE DES DONNÉES
            $entityManager->persist($candidature);
            $entityManager->persist($recommendation);
            $entityManager->flush();

            // Message de succès et redirection
            $this->addFlash('success', 'Votre candidature a été transmise avec succès !');
            return $this->redirectToRoute('app_candidature_success');
        }

        return $this->render('front/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    
    #[Route('/admin/offres/{id}/candidatures', name: 'app_admin_offre_candidatures')]
     public function adminScores(int $id, EntityManagerInterface $em): Response
{
    $offre = $em->getRepository(\App\Entity\Offer::class)->find($id);

    if (!$offre) {
        throw $this->createNotFoundException('Offre introuvable');
    }

    // Récupérer les candidatures liées à cette offre
    $candidatures = $em->getRepository(\App\Entity\Candidature::class)
                       ->findBy(['offre' => $offre]);

    // TRI : Du plus recommandé au moins recommandé (Décroissant)
    // On utilise usort pour comparer les scores des recommandations liées
    usort($candidatures, function($a, $b) {
        $scoreA = $a->getRecommendation() ? $a->getRecommendation()->getScore() : 0;
        $scoreB = $b->getRecommendation() ? $b->getRecommendation()->getScore() : 0;
        
        // Pour un tri décroissant (le plus grand score en premier)
        return $scoreB <=> $scoreA;
    });

    return $this->render('admin/scores.html.twig', [
        'offre' => $offre,
        'candidatures' => $candidatures,
    ]);
}
    /**
     * PAGE DE SUCCÈS
     */
    #[Route('/success', name: 'app_candidature_success')]
    public function success(): Response 
    { 
        return $this->render('front/success.html.twig');
    }
}
