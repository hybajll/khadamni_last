<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Repository\CandidatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class KhadamniController extends AbstractController
{
    // FRONT OFFICE : Formulaire pour postuler
    #[Route('/postuler', name: 'app_front_postuler', methods: ['GET', 'POST'])]
    public function postuler(Request $request, EntityManagerInterface $em): Response
    {
        // Ici tu pourrais intégrer ton formulaire CandidatureType
        return $this->render('front/postuler.html.twig');
    }

    // BACK OFFICE : Interface Société avec pourcentage de compatibilité
    #[Route('/admin/candidats', name: 'app_back_admin', methods: ['GET'])]
    public function adminList(CandidatureRepository $repo): Response
    {
        return $this->render('back/liste_candidats.html.twig', [
            'candidatures' => $repo->findAll(),
        ]);
    }
}