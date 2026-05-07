<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\ReponseReclamation;
use App\Entity\Society;
use App\Enum\StatutReclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use App\Service\AiAssistantService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReclamationUserController extends AbstractController
{
    #[Route('/reclamations/support', name: 'app_user_reclamation_index', methods: ['GET', 'POST'])]
    public function index(
        ReclamationRepository $reclamationRepository, 
        Request $request, 
        EntityManagerInterface $entityManager,
        AiAssistantService $aiService,
        NotificationService $notificationService
    ): Response {
        $connectedUser = $this->getUser();
        
        if (!$connectedUser) {
            return $this->redirectToRoute('app_login');
        }

        // --- VERIFICATION DE RECLAMATION EN COURS ---
        // On définit les critères selon le type d'utilisateur
        $ownerCriteria = ($connectedUser instanceof Society) ? ['society' => $connectedUser] : ['user' => $connectedUser];
        
        // On cherche s'il existe une réclamation qui n'est PAS dans un état final
        $activeReclamation = $reclamationRepository->createQueryBuilder('r')
            ->where('r.statut NOT IN (:final_states)')
            ->andWhere($connectedUser instanceof Society ? 'r.society = :owner' : 'r.user = :owner')
            ->setParameter('final_states', [StatutReclamation::RESOLUE, StatutReclamation::REJETEE])
            ->setParameter('owner', $connectedUser)
            ->getQuery()
            ->getResult();

        $hasActiveReclamation = !empty($activeReclamation);

        // Initialisation du formulaire
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        // --- TRAITEMENT DU FORMULAIRE ---
        if ($form->isSubmitted() && $form->isValid()) {
            // Blocage de sécurité : interdire l'envoi si une réclamation est active
            if ($hasActiveReclamation) {
                $this->addFlash('error', 'Action impossible : vous avez déjà une réclamation en cours de traitement.');
                return $this->redirectToRoute('app_user_reclamation_index');
            }

            // Attribution du propriétaire
            if ($connectedUser instanceof Society) {
                $reclamation->setSociety($connectedUser);
            } else {
                $reclamation->setUser($connectedUser);
            }

            $reclamation->setDateCreation(new \DateTime());
            $reclamation->setStatut(StatutReclamation::EN_ATTENTE);
            
            $entityManager->persist($reclamation);
            $entityManager->flush();

            // Logique IA & Notification
            if ($aiService->processNewReclamation($reclamation)) {
                $similar = $reclamationRepository->findSimilarByTypeWithResponse(
                    $reclamation->getType(), 
                    $reclamation->getIdReclamation()
                );
                
                $context = null;
                if ($similar && !$similar->getReponseReclamations()->isEmpty()) {
                    $context = $similar->getReponseReclamations()->first()->getMessage();
                }

                $reply = $notificationService->generateAndStoreAiReply($reclamation, $context);
                
                if ($reply) {
                    $notificationService->sendStatusUpdateEmail($reclamation, $reply->getMessage());
                    $this->addFlash('success', 'Une assistance IA a répondu à votre message.');
                }
            } else {
                $this->addFlash('success', 'Votre réclamation a été transmise à nos conseillers.');
            }

            return $this->redirectToRoute('app_user_reclamation_index', [
                'open_chat' => $reclamation->getIdReclamation()
            ]);
        }

        // Récupération de l'historique
        $myReclamations = $reclamationRepository->findBy($ownerCriteria, ['date_creation' => 'DESC']);

        $template = ($connectedUser instanceof Society) 
            ? 'society/reclamation_support.html.twig' 
            : 'user/reclamation_support.html.twig';

        return $this->render($template, [
            'reclamations' => $myReclamations,
            'form' => $form->createView(),
            'hasActiveReclamation' => $hasActiveReclamation // Utile pour griser le bouton en Twig
        ]);
    }

    #[Route('/reclamation/{id}/reply', name: 'app_user_reclamation_reply', methods: ['POST'])]
    public function reply(Reclamation $reclamation, Request $request, EntityManagerInterface $entityManager): Response 
    {
        $connectedUser = $this->getUser();
        $messageContent = $request->request->get('message');
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('reply' . $reclamation->getIdReclamation(), $token)) {
            $this->addFlash('error', 'Session expirée.');
            return $this->redirectToRoute('app_user_reclamation_index');
        }

        if (!empty(trim($messageContent))) {
            $reponse = new ReponseReclamation();
            $reponse->setReclamation($reclamation);
            $reponse->setMessage($messageContent);
            $reponse->setDateReponse(new \DateTime());
            
            if ($connectedUser instanceof Society) {
                $reponse->setSocietyAuteur($connectedUser);
            } else {
                $reponse->setAuteur($connectedUser);
            }

            $entityManager->persist($reponse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_reclamation_index', ['open_chat' => $reclamation->getIdReclamation()]);
    }

    /*------------test gemini-------------*/
    #[Route('/test-gemini', name: 'test_gemini')]
    public function testGemini(AiAssistantService $aiService): Response
    {
        $result = $aiService->generateAiResponse(
            'STAGE', 
            null, 
            'Je cherche un stage en informatique mais je ne trouve pas d\'offres'
        );
        
        return $this->json([
            'reponse_ia' => $result,
            'source' => str_contains($result, 'support@khadamni.tn') ? 'database_or_fallback' : 'gemini',
            'contient_phrase_generique' => str_contains($result, 'équipe support')
        ]);
    }
}