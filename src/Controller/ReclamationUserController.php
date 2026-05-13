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
    #[Route('/reclamations', name: 'app_reclamation_index', methods: ['GET', 'POST'])]
    public function index(
        ReclamationRepository $reclamationRepository, 
        Request $request, 
        EntityManagerInterface $entityManager,
        AiAssistantService $aiService,
        NotificationService $notificationService
    ): Response {
        $connectedUser = $this->getUser();

        // NOTE : La vérification de connexion est maintenant gérée par security.yaml

        $isSociety = $connectedUser instanceof Society;
        $ownerCriteria = $isSociety ? ['society' => $connectedUser] : ['user' => $connectedUser];

        // Vérification de réclamation active
        $activeReclamation = $reclamationRepository->createQueryBuilder('r')
            ->where('r.statut NOT IN (:final_states)')
            ->andWhere($isSociety ? 'r.society = :owner' : 'r.user = :owner')
            ->setParameter('final_states', [StatutReclamation::RESOLUE, StatutReclamation::REJETEE])
            ->setParameter('owner', $connectedUser)
            ->getQuery()
            ->getResult();

        $hasActiveReclamation = !empty($activeReclamation);

        // Formulaire
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($hasActiveReclamation) {
                $this->addFlash('error', 'Vous avez déjà une réclamation en cours.');
                return $this->redirectToRoute('app_reclamation_index');
            }

            $isSociety ? $reclamation->setSociety($connectedUser) : $reclamation->setUser($connectedUser);
            $reclamation->setDateCreation(new \DateTime());
            $reclamation->setStatut(StatutReclamation::EN_ATTENTE);
            
            $entityManager->persist($reclamation);
            $entityManager->flush();

            // Logique IA Gemini
            if ($aiService->processNewReclamation($reclamation)) {
                $similar = $reclamationRepository->findSimilarByTypeWithResponse(
                    $reclamation->getType(), 
                    $reclamation->getIdReclamation()
                );
                
                $context = ($similar && !$similar->getReponseReclamations()->isEmpty()) 
                    ? $similar->getReponseReclamations()->first()->getMessage() 
                    : null;

                $reply = $notificationService->generateAndStoreAiReply($reclamation, $context);
                if ($reply) {
                    $notificationService->sendStatusUpdateEmail($reclamation, $reply->getMessage());
                    $this->addFlash('success', 'L\'assistance IA a répondu à votre demande.');
                }
            }

            return $this->redirectToRoute('app_reclamation_index', ['open_chat' => $reclamation->getIdReclamation()]);
        }

        $myReclamations = $reclamationRepository->findBy($ownerCriteria, ['date_creation' => 'DESC']);
        $template = $isSociety ? 'admin/societies/reclamation_support.html.twig' : 'user/reclamation_support.html.twig';

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

        if ($reclamation->getUser() !== $connectedUser && $reclamation->getSociety() !== $connectedUser) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $messageContent = $request->request->get('message');
        if (!empty(trim($messageContent))) {
            $reponse = new ReponseReclamation();
            $reponse->setReclamation($reclamation);
            $reponse->setMessage($messageContent);
            $reponse->setDateReponse(new \DateTime());
            
            $connectedUser instanceof Society ? $reponse->setSocietyAuteur($connectedUser) : $reponse->setAuteur($connectedUser);

            $entityManager->persist($reponse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamation_index', ['open_chat' => $reclamation->getIdReclamation()]);
    }
}