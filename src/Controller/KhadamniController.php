<?php

namespace App\Controller;

use App\Repository\CandidatureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class KhadamniController extends AbstractController
{
    // Legacy/demo route kept for teammate work; real application form is handled by CandidatureController (/postuler).
    #[Route('/front/postuler', name: 'app_front_postuler', methods: ['GET'])]
    public function postuler(): Response
    {
        return $this->render('front/postuler.html.twig');
    }

    // BACK OFFICE : Interface Société avec pourcentage de compatibilité (teammate module)
    #[Route('/admin/candidats', name: 'app_back_admin', methods: ['GET'])]
    public function adminList(CandidatureRepository $repo): Response
    {
        return $this->render('back/liste_candidats.html.twig', [
            'candidatures' => $repo->findAll(),
        ]);
    }
}

