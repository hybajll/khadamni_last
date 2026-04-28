<?php

namespace App\Controller;

use App\Repository\OfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(OfferRepository $offerRepository): Response
    {
        $offers = $offerRepository->findAllActive();
        $offers = array_slice($offers, 0, 9);

        return $this->render('home/index.html.twig', [
            'offers' => $offers,
        ]);
    }
}

