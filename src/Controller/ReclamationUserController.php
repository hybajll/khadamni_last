<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\ReponseReclamation;
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
        $user = $this->getUser();
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation->setUser($user);
            $reclamation->setDateCreation(new \DateTime());
            $reclamation->setStatut(StatutReclamation::EN_ATTENTE);
            $entityManager->persist($reclamation);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_reclamation_index');
        }

        $myReclamations = $reclamationRepository->findBy(
            ['user' => $user],
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
        if ($reclamation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Accès non autorisé.");
        }

        $statut = $reclamation->getStatut();
        if ($statut === StatutReclamation::RESOLUE || $statut === StatutReclamation::REJETEE) {
            $this->addFlash('error', 'Cette réclamation est clôturée. Vous ne pouvez plus répondre.');
            return $this->redirectToRoute('app_user_reclamation_index');
        }

        $messageContent = $request->request->get('message');
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('reply' . $reclamation->getIdReclamation(), $token)) {
            return $this->redirectToRoute('app_user_reclamation_index');
        }

        if (!empty(trim($messageContent))) {
            $reponse = new ReponseReclamation();
            $reponse->setReclamation($reclamation);
            $reponse->setMessage($messageContent);
            $reponse->setDateReponse(new \DateTime());
            // Utilisation de l'objet User directement (Héritage)
            $reponse->setAuteur($this->getUser());

            $entityManager->persist($reponse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_reclamation_index', [
            'open_chat' => $reclamation->getIdReclamation()
        ]);
    }
}