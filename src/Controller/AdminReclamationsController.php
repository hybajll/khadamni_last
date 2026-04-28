<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
#[Route('/admin/reclamations')]
final class AdminReclamationsController extends AbstractController
{
    #[Route('', name: 'app_admin_reclamations_index', methods: ['GET'])]
    public function index(): Response
    {
        // Minimal starter page; can be expanded later to list and answer reclamations.
        return $this->render('admin/reclamations/index.html.twig');
    }

    #[Route('/check-messages', name: 'app_admin_check_messages', methods: ['GET'])]
    public function checkMessages(): JsonResponse
    {
        // Minimal endpoint used by the dashboard badge (0 by default).
        return new JsonResponse([
            'newMessagesCount' => 0,
        ]);
    }
}

