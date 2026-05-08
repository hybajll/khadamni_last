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

        // --- 1. VERIFICATION DE RECLAMATION EN COURS ---
        $ownerCriteria = ($connectedUser instanceof Society) ? ['society' => $connectedUser] : ['user' => $connectedUser];
        
        $activeReclamation = $reclamationRepository->createQueryBuilder('r')
            ->where('r.statut NOT IN (:final_states)')
            ->andWhere($connectedUser instanceof Society ? 'r.society = :owner' : 'r.user = :owner')
            ->setParameter('final_states', [StatutReclamation::RESOLUE, StatutReclamation::REJETEE])
            ->setParameter('owner', $connectedUser)
            ->getQuery()
            ->getResult();

        $hasActiveReclamation = !empty($activeReclamation);

        // --- 2. TRAITEMENT DU FORMULAIRE DE NOUVELLE RECLAMATION ---
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($hasActiveReclamation) {
                $this->addFlash('error', 'Action impossible : vous avez déjà une réclamation en cours.');
                return $this->redirectToRoute('app_user_reclamation_index');
            }

            if ($connectedUser instanceof Society) {
                $reclamation->setSociety($connectedUser);
            } else {
                $reclamation->setUser($connectedUser);
            }

            $reclamation->setDateCreation(new \DateTime());
            $reclamation->setStatut(StatutReclamation::EN_ATTENTE);
            
            $entityManager->persist($reclamation);
            $entityManager->flush();

            // Logique IA
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

        // --- 3. RÉCUPÉRATION DE L'HISTORIQUE ---
        $myReclamations = $reclamationRepository->findBy($ownerCriteria, ['date_creation' => 'DESC']);

        $template = ($connectedUser instanceof Society) 
            ? 'society/reclamation_support.html.twig' 
            : 'user/reclamation_support.html.twig';

        return $this->render($template, [
            'reclamations' => $myReclamations,
            'form' => $form->createView(),
            'hasActiveReclamation' => $hasActiveReclamation
        ]);
    }

    #[Route('/reclamation/{id}/reply', name: 'app_user_reclamation_reply', methods: ['POST'])]
    public function reply(Reclamation $reclamation, Request $request, EntityManagerInterface $entityManager): Response 
    {
        $connectedUser = $this->getUser();
        $messageContent = $request->request->get('message');
        $token = $request->request->get('_token');

        // Validation CSRF
        if (!$this->isCsrfTokenValid('reply' . $reclamation->getIdReclamation(), $token)) {
            $this->addFlash('error', 'Session expirée.');
            return $this->redirectToRoute('app_user_reclamation_index');
        }

        // --- BLOCAGE SI RÉCLAMATION TERMINÉE ---
        $statutsClotures = [StatutReclamation::RESOLUE, StatutReclamation::REJETEE];
        if (in_array($reclamation->getStatut(), $statutsClotures)) {
            $this->addFlash('error', 'Cette réclamation est clôturée. Impossible d\'envoyer un message.');
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

        return $this->redirectToRoute('app_user_reclamation_index', [
            'open_chat' => $reclamation->getIdReclamation()
        ]);
    }

    #[Route('/test-gemini', name: 'test_gemini')]
    public function testGemini(AiAssistantService $aiService): Response
    {
        $result = $aiService->generateAiResponse('STAGE', null, 'Je cherche un stage en informatique');
        
        return $this->json([
            'reponse_ia' => $result,
            'contient_phrase_generique' => str_contains($result, 'équipe support')
        ]);
    }
}