<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\ReponseReclamation;
use App\Entity\Society;
use App\Entity\User;
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
    #[Route('/dashboard', name: 'app_user_dashboard', methods: ['GET'])]
    public function dashboard(): Response 
    {
        return $this->render('user/home.html.twig');
    }

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

        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Attribution de l'auteur de la réclamation
            if ($connectedUser instanceof Society) {
                $reclamation->setSociety($connectedUser);
                $reclamation->setUser(null);
            } elseif ($connectedUser instanceof User) {
                $reclamation->setUser($connectedUser);
                $reclamation->setSociety(null);
            }

            $reclamation->setDateCreation(new \DateTime());
            $reclamation->setStatut(StatutReclamation::EN_ATTENTE);
            
            $entityManager->persist($reclamation);
            $entityManager->flush(); // L'ID est généré ici

            // --- LOGIQUE IA & NOTIFICATION ---
            // 1. Analyse du contenu
            $aiService->processNewReclamation($reclamation);
            
            // 2. Génération et stockage de la réponse IA en BDD
            $reply = $notificationService->generateAndStoreAiReply($reclamation, null);
            
            // 3. Envoi de l'email de confirmation avec la réponse IA
            $notificationService->sendStatusUpdateEmail($reclamation, $reply->getMessage());

            $this->addFlash('success', 'Votre réclamation a été envoyée.');

            // REDIRECTION avec l'ID pour ouvrir le chat automatiquement en JS
            return $this->redirectToRoute('app_user_reclamation_index', [
                'open_chat' => $reclamation->getIdReclamation()
            ]);
        }

        // Filtrage des réclamations pour n'afficher que les siennes
        $criteria = ($connectedUser instanceof Society) 
            ? ['society' => $connectedUser] 
            : ['user' => $connectedUser];

        $myReclamations = $reclamationRepository->findBy(
            $criteria,
            ['date_creation' => 'DESC']
        );

        return $this->render('user/reclamation_support.html.twig', [
            'reclamations' => $myReclamations,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reclamation/{id}/reply', name: 'app_user_reclamation_reply', methods: ['POST'])]
    public function reply(
        Reclamation $reclamation, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response {
        $connectedUser = $this->getUser();

        // Vérification de sécurité : l'utilisateur possède-t-il cette réclamation ?
        $isOwner = ($reclamation->getSociety() === $connectedUser || $reclamation->getUser() === $connectedUser);
        
        if (!$isOwner) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à accéder à cette ressource.");
        }

        // Empêcher la réponse si déjà résolue ou rejetée
        if (in_array($reclamation->getStatut(), [StatutReclamation::RESOLUE, StatutReclamation::REJETEE])) {
            $this->addFlash('error', 'Cette réclamation est clôturée. Vous ne pouvez plus envoyer de messages.');
            return $this->redirectToRoute('app_user_reclamation_index');
        }

        $messageContent = $request->request->get('message');
        $token = $request->request->get('_token');

        // Validation du jeton CSRF
        if (!$this->isCsrfTokenValid('reply' . $reclamation->getIdReclamation(), $token)) {
            $this->addFlash('error', 'Session invalide, veuillez réessayer.');
            return $this->redirectToRoute('app_user_reclamation_index');
        }

        if (!empty(trim($messageContent))) {
            $reponse = new ReponseReclamation();
            $reponse->setReclamation($reclamation);
            $reponse->setMessage($messageContent);
            $reponse->setDateReponse(new \DateTime());
            
            // Gestion de l'auteur de la réponse
            if ($connectedUser instanceof Society) {
                $reponse->setSocietyAuteur($connectedUser);
                $reponse->setAuteur(null);
            } else {
                $reponse->setAuteur($connectedUser);
                $reponse->setSocietyAuteur(null);
            }

            $entityManager->persist($reponse);
            $entityManager->flush();
        }

        // On réouvre le chat après avoir posté un message
        return $this->redirectToRoute('app_user_reclamation_index', [
            'open_chat' => $reclamation->getIdReclamation()
        ]);
    }
}