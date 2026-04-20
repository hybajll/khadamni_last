<?php

namespace App\Controller;

use App\Entity\Offer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PublicOfferController extends AbstractController
{
    #[Route('/offers/{id}', name: 'app_offer_public_show', methods: ['GET'])]
    public function show(Offer $offer): Response
    {
        return $this->render('offers/public_show.html.twig', [
            'offer' => $offer,
        ]);
    }
}

