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

        // 1. Récupération des paramètres de filtrage
        $tab = $request->query->get('tab', 'societe');
        $searchDate = $request->query->get('searchDate');

        // 2. Préparation de la date pour le Repository
        $dateImmutable = null;
        if ($searchDate) {
            try {
                $dateImmutable = new \DateTimeImmutable($searchDate);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Format de date invalide.');
            }
        }

        // 3. Utilisation de la méthode unique et robuste du Repository
        // Cette méthode gère l'onglet ET la date simultanément
        $reclamations = $reclamationRepository->findByTabAndDate($tab, $dateImmutable, $limit, $offset);
        $totalReclamations = $reclamationRepository->countByTabAndDate($tab, $dateImmutable);

        $totalPages = ceil($totalReclamations / $limit);

        return $this->render('admin/reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'stats' => $reclamationRepository->getStatistics(),
            'statuts_disponibles' => StatutReclamation::cases(),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'searchDate' => $searchDate,
            'activeTab' => $tab,
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
            $entityManager->persist($reclamation);
            $entityManager->flush();

            // Logique IA & Notification
            $isSimple = $aiService->processNewReclamation($reclamation);
            $reply = $notificationService->generateAndStoreAiReply($reclamation, null);
            $notificationService->sendStatusUpdateEmail($reclamation, $reply->getMessage());

            if (!$isSimple) {
                $this->addFlash('warning', 'Réclamation complexe : intervention admin requise.');
            }
            $this->addFlash('success', 'Réclamation créée et réponse IA envoyée.');

            return $this->redirectToCorrectList($reclamation);
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
                $notificationService->sendStatusUpdateEmail($reclamation);
                $this->addFlash('success', 'Statut RESOLU et email envoyé.');
            } else {
                $this->addFlash('success', 'Statut mis à jour.');
            }
        }

        return $this->redirectToCorrectList($reclamation);
    }

    #[Route('/{id}/edit', name: 'app_admin_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation->setDateModification(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'La réclamation a été modifiée avec succès.');
            return $this->redirectToCorrectList($reclamation);
        }

        return $this->render('admin/reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reclamation->getIdReclamation(), $request->request->get('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            $this->addFlash('danger', 'Réclamation supprimée.');
        }
        
        // Comme l'objet est supprimé, on définit manuellement le tab ou on passe l'objet avant suppression
        $tab = ($reclamation->getUser() !== null) ? 'user' : 'societe';
        return $this->redirectToRoute('app_admin_reclamation_index', ['tab' => $tab]);
    }

    private function redirectToCorrectList(Reclamation $reclamation): Response
    {
        $tab = ($reclamation->getUser() !== null) ? 'user' : 'societe';
        return $this->redirectToRoute('app_admin_reclamation_index', ['tab' => $tab]);
    }
}