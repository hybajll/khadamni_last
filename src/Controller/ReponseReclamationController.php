<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\ReponseReclamation;
use App\Entity\Admin;
use App\Enum\StatutReclamation;
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
    #[Route('/', name: 'app_admin_reclamations_index', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        return $this->render('admin/reclamation/index.html.twig', [
            'reclamations' => $reclamationRepository->findBy([], ['date_creation' => 'DESC']),
        ]);
    }

    #[Route('/{id}/chat', name: 'app_admin_reclamation_chat', methods: ['GET', 'POST'])]
    public function chat(
        Reclamation $reclamation, 
        Request $request, 
        EntityManagerInterface $entityManager,
        ReponseReclamationRepository $reponseRepo
    ): Response {
        
        if ($request->isMethod('POST')) {
            $messageContent = $request->request->get('message');
            $token = $request->request->get('_token');

            if ($this->isCsrfTokenValid('admin_reply' . $reclamation->getIdReclamation(), $token)) {
                if (!empty(trim($messageContent))) {
                    $reponse = new ReponseReclamation();
                    $reponse->setReclamation($reclamation);
                    $reponse->setMessage($messageContent);
                    $reponse->setAuteur($this->getUser()); 
                    $reponse->setDateReponse(new \DateTime());

                    $reclamation->setStatut(StatutReclamation::EN_COURS);
                    
                    $entityManager->persist($reponse);
                    $entityManager->flush();
                    
                    return $this->redirectToRoute('app_admin_reclamation_chat', ['id' => $reclamation->getIdReclamation()]);
                }
            }
        }

        // On récupère TOUS les messages pour être sûr que l'IA apparaisse
        $messages = $reponseRepo->findBy(['reclamation' => $reclamation], ['date_reponse' => 'ASC']);

        return $this->render('admin/reclamation/chat.html.twig', [
            'reclamation' => $reclamation,
            'messages' => $messages,
        ]);
    }

    #[Route('/api/check-messages', name: 'app_admin_check_messages', methods: ['GET'])]
    public function checkNewMessages(ReponseReclamationRepository $reponseRepo): Response
    {
        // On récupère les 10 derniers messages globaux
        $recentMessages = $reponseRepo->findBy([], ['date_reponse' => 'DESC'], 10); 
        $notifications = [];
        $processedIds = [];

        foreach ($recentMessages as $msg) {
            $rec = $msg->getReclamation();
            if (!$rec) continue;

            $recId = $rec->getIdReclamation();
            if (in_array($recId, $processedIds)) continue;

            // On notifie si c'est un message client OU un message de l'IA (auteur null)
            if ($msg->getAuteur() === null || !($msg->getAuteur() instanceof Admin)) {
                $notifications[] = [
                    'username' => $msg->getAuteur() ? $rec->getUser()->getNom() : 'Assistant IA',
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