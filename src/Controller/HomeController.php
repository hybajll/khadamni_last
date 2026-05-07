<?php

namespace App\Controller;

use App\Repository\OfferRepository;
use App\Repository\SocietyRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        OfferRepository $offerRepository,
        UserRepository $userRepository,
        SocietyRepository $societyRepository,
    ): Response
    {
        $offersAll = $offerRepository->findAllActive();
        $offers = array_slice($offersAll, 0, 9);

        return $this->render('home/index.html.twig', [
            'offers' => $offers,
            'stats' => [
                'offers' => count($offersAll),
                'candidates' => $userRepository->countNonAdmins(),
                'societies' => $societyRepository->count(['isActive' => true]),
            ],
        ]);
    }
}
