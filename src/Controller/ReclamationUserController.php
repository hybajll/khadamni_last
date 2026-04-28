<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\ReponseReclamation;
use App\Entity\Society;
use App\Entity\User;
use App\Enum\StatutReclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
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
        EntityManagerInterface $entityManager
    ): Response {
        $connectedUser = $this->getUser();
        
        // Sécurité : Rediriger si non connecté
        if (!$connectedUser) {
            return $this->redirectToRoute('app_login');
        }

        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // On utilise instanceof qui est géré nativement par Symfony/Doctrine
            // même pour les Proxies.
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
            $entityManager->flush();

            $this->addFlash('success', 'Votre réclamation a été envoyée.');
            return $this->redirectToRoute('app_user_reclamation_index');
        }

        // Filtrage dynamique pour la liste des réclamations
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

        // Vérification robuste de la propriété (évite que User A réponde à User B)
        $isOwner = ($reclamation->getSociety() === $connectedUser || $reclamation->getUser() === $connectedUser);
        
        if (!$isOwner) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à répondre à cette réclamation.");
        }

        // Vérification du statut (empêche de répondre à une réclamation fermée)
        if (in_array($reclamation->getStatut(), [StatutReclamation::RESOLUE, StatutReclamation::REJETEE])) {
            $this->addFlash('error', 'Cette réclamation est clôturée.');
            return $this->redirectToRoute('app_user_reclamation_index');
        }

        $messageContent = $request->request->get('message');
        $token = $request->request->get('_token');

        // Validation CSRF et contenu
        if (!$this->isCsrfTokenValid('reply' . $reclamation->getIdReclamation(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_user_reclamation_index');
        }

      if (!empty(trim($messageContent))) {
            $reponse = new ReponseReclamation();
            $reponse->setReclamation($reclamation);
            $reponse->setMessage($messageContent);
            $reponse->setDateReponse(new \DateTime());
            
            // Correction du type d'auteur
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

        return $this->redirectToRoute('app_user_reclamation_index', [
            'open_chat' => $reclamation->getIdReclamation()
        ]);
    }
}