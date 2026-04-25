<?php

namespace App\Controller;

use App\Form\ReclamationType;
use App\Entity\Reclamation;
use App\Enum\StatutReclamation;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\NotificationService;
use App\Service\AiAssistantService;

#[Route('/admin/reclamations')]
class ReclamationController extends AbstractController
{
    #[Route('/', name: 'app_admin_reclamation_index', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository, Request $request): Response
    {
        $page = (int) $request->query->get('page', 1);
        $limit = 5; 
        $offset = ($page - 1) * $limit;

        $stats = $reclamationRepository->getStatistics();
        $totalReclamations = $stats['total'];
        $totalPages = ceil($totalReclamations / $limit);

        $reclamations = $reclamationRepository->findBy(
            [], 
            ['date_creation' => 'DESC'], 
            $limit, 
            $offset
        );

        return $this->render('admin/reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'stats' => $stats,
            'statuts_disponibles' => StatutReclamation::cases(),
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }
#[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        AiAssistantService $aiService,
        NotificationService $notificationService
    ): Response {
        $reclamation = new Reclamation();
        $reclamation->setUser($this->getUser());

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1. Persister la réclamation
            $entityManager->persist($reclamation);
            $entityManager->flush();

            // 2. Déterminer si c'est simple ou complexe
            $isSimple = $aiService->processNewReclamation($reclamation);

            // 3. IA : Générer, Enregistrer en BDD et récupérer l'objet réponse
            $reply = $notificationService->generateAndStoreAiReply($reclamation, null);

            // 4. Envoyer l'email avec le message déjà stocké
            $notificationService->sendStatusUpdateEmail($reclamation, $reply->getMessage());

            // 5. Flash messages
            if (!$isSimple) {
                $this->addFlash('warning', 'Réclamation complexe : intervention admin requise.');
            }
            $this->addFlash('success', 'Réclamation créée et réponse IA envoyée.');

            return $this->redirectToRoute('app_admin_reclamation_index');
        }

        return $this->render('admin/reclamation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/statut', name: 'app_admin_reclamation_set_statut', methods: ['POST'])]
    public function setStatut(
        int $id, 
        Request $request, 
        ReclamationRepository $reclamationRepository, 
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response {
        $reclamation = $reclamationRepository->find($id);
        if (!$reclamation) throw $this->createNotFoundException();

        $nouveauStatut = StatutReclamation::tryFrom($request->request->get('statut'));

        if ($nouveauStatut) {
            $reclamation->setStatut($nouveauStatut);
            $reclamation->setDateModification(new \DateTime());
            $entityManager->flush();

            if ($nouveauStatut === StatutReclamation::RESOLUE) {
                // Ici on laisse l'email générer son propre message IA si besoin
                $notificationService->sendStatusUpdateEmail($reclamation);
                $this->addFlash('success', 'Statut RESOLU et email envoyé.');
            } else {
                $this->addFlash('success', 'Statut mis à jour.');
            }
        }

        return $this->redirectToRoute('app_admin_reclamation_index');
    }

    #[Route('/{id}/delete', name: 'app_admin_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response 
    {
        if ($this->isCsrfTokenValid('delete' . $reclamation->getIdReclamation(), $request->request->get('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            $this->addFlash('danger', 'Réclamation supprimée.');
        }
        return $this->redirectToRoute('app_admin_reclamation_index');
    }

    #[Route('/{id}/edit', name: 'app_admin_reclamation_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
{
    // On crée le formulaire en passant l'objet existant
    $form = $this->createForm(ReclamationType::class, $reclamation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $reclamation->setDateModification(new \DateTime());
        $entityManager->flush();

        $this->addFlash('success', 'La réclamation a été modifiée avec succès.');

        return $this->redirectToRoute('app_admin_reclamation_index');
    }

    return $this->render('admin/reclamation/edit.html.twig', [
        'reclamation' => $reclamation,
        'form' => $form->createView(),
    ]);
}
}