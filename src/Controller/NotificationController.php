<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\InAppNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly InAppNotificationService    $notificationService,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Page principale : liste de toutes les notifications.
     */
    #[Route('', name: 'app_notifications_index', methods: ['GET'])]
    public function index(): Response
    {
        $user          = $this->getUser();
        $notifications = $this->notificationRepository->findByUser($user, 50);

        // Marquer les non-lues comme lues dès qu'on ouvre la page
        $this->notificationService->markAllRead($user);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * API JSON : retourne le badge (nombre non lus) + les 10 dernières notifs
     * pour alimenter la cloche dans la navbar en temps réel (polling AJAX).
     *
     * GET /notifications/api/summary
     */
    #[Route('/api/summary', name: 'app_notifications_api_summary', methods: ['GET'])]
    public function apiSummary(): JsonResponse
    {
        $user          = $this->getUser();
        $unread        = $this->notificationRepository->findUnreadByUser($user);
        $all           = $this->notificationRepository->findByUser($user, 10);

        $items = array_map(fn(Notification $n) => [
            'id'        => $n->getId(),
            'type'      => $n->getType(),
            'title'     => $n->getTitle(),
            'message'   => $n->getMessage(),
            'link'      => $n->getLink(),
            'isRead'    => $n->isRead(),
            'icon'      => $n->getIcon(),
            'createdAt' => $n->getCreatedAt()->format('Y-m-d H:i'),
        ], $all);

        return $this->json([
            'unreadCount' => count($unread),
            'items'       => $items,
        ]);
    }

    /**
     * Marquer une notification comme lue (AJAX).
     *
     * POST /notifications/{id}/read
     */
    #[Route('/{id}/read', name: 'app_notifications_mark_read', methods: ['POST'])]
    public function markRead(Notification $notification): JsonResponse
    {
        // Sécurité : seul le propriétaire peut marquer sa notif
        if ($notification->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $notification->markAsRead();
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Marquer TOUTES les notifications comme lues.
     *
     * POST /notifications/read-all
     */
    #[Route('/read-all', name: 'app_notifications_mark_all_read', methods: ['POST'])]
    public function markAllRead(): JsonResponse
    {
        $this->notificationService->markAllRead($this->getUser());

        return $this->json(['success' => true]);
    }

    /**
     * Supprimer une notification.
     *
     * DELETE /notifications/{id}
     */
    #[Route('/{id}', name: 'app_notifications_delete', methods: ['DELETE'])]
    public function delete(Notification $notification): JsonResponse
    {
        if ($notification->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $this->em->remove($notification);
        $this->em->flush();

        return $this->json(['success' => true]);
    }
}
