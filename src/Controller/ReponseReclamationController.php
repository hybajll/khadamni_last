<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\ReponseReclamation;
use App\Entity\Admin;
use App\Repository\ReclamationRepository;
use App\Repository\ReponseReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/reclamations')]
class ReponseReclamationController extends AbstractController
{
    /**
     * Liste toutes les réclamations pour l'admin
     */
    #[Route('/', name: 'app_admin_reclamations_index', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        return $this->render('admin/reclamation/index.html.twig', [
            'reclamations' => $reclamationRepository->findBy([], ['date_creation' => 'DESC']),
        ]);
    }

    /**
     * Interface de chat pour l'admin
     */
    #[Route('/{id}/chat', name: 'app_admin_reclamation_chat', methods: ['GET', 'POST'])]
    public function chat(
        Reclamation $reclamation, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response {
        
        if ($request->isMethod('POST')) {
            $messageContent = $request->request->get('message');
            $token = $request->request->get('_token');

            if (!$this->isCsrfTokenValid('admin_reply' . $reclamation->getIdReclamation(), $token)) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_admin_reclamation_chat', ['id' => $reclamation->getIdReclamation()]);
            }

            if (!empty(trim($messageContent))) {
                $reponse = new ReponseReclamation();
                $reponse->setReclamation($reclamation);
                $reponse->setMessage($messageContent);
                $reponse->setAuteur($this->getUser()); // Enregistre l'Admin connecté

                $entityManager->persist($reponse);
                $entityManager->flush();
                
                return $this->redirectToRoute('app_admin_reclamation_chat', [
                    'id' => $reclamation->getIdReclamation()
                ]);
            }
        }

        return $this->render('admin/reclamation/chat.html.twig', [
            'reclamation' => $reclamation,
            'messages' => $reclamation->getReponseReclamations(),
        ]);
    }

    /**
     * API pour les notifications en temps réel
     */
    #[Route('/api/check-messages', name: 'app_admin_check_messages', methods: ['GET'])]
    public function checkNewMessages(ReponseReclamationRepository $reponseRepo): Response
    {
        $recentMessages = $reponseRepo->findRecentMessagesForAdmin(); 
        $notifications = [];
        $processedIds = [];

        foreach ($recentMessages as $msg) {
            $rec = $msg->getReclamation();
            $recId = $rec->getIdReclamation();

            if (in_array($recId, $processedIds)) continue;

            // Si le dernier message vient d'un utilisateur (pas un Admin), on notifie
            if (!($msg->getAuteur() instanceof Admin)) {
                $notifications[] = [
                    'username' => $rec->getUser() ? $rec->getUser()->getNom() : 'Anonyme',
                    'reclamationId' => $recId,
                    'text' => mb_strimwidth($msg->getMessage(), 0, 45, "...")
                ];
                $processedIds[] = $recId;
            }
        }

        return $this->json([
            'newMessagesCount' => count($notifications),
            'details' => $notifications
        ]);
    }
}