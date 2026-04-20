<?php

namespace App\Controller;

use App\Entity\Society;
use App\Form\SocietyRegistrationType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/society')]
class SocietyRegistrationController extends AbstractController
{
    #[Route('/register', name: 'society_register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($this->getUser() && $this->isGranted('ROLE_SOCIETY')) {
            $this->addFlash('info', 'Vous êtes déjà connecté. Déconnectez-vous pour créer un nouveau compte.');
            return $this->redirectToRoute('society_dashboard');
        }

        $society = new Society();
        $form = $this->createForm(SocietyRegistrationType::class, $society);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $society
                ->setPassword($passwordHasher->hashPassword($society, $society->getPassword()))
                ->setIsActive(true)
                ->setCreatedAt(new \DateTimeImmutable());

            try {
                $entityManager->persist($society);
                $entityManager->flush();

                $this->addFlash('success', 'Compte société créé avec succès. Vous pouvez vous connecter.');
                return $this->redirectToRoute('society_login');
            } catch (UniqueConstraintViolationException) {
                // Extra safety (DB unique index). Normally handled by UniqueEntity validator.
                $this->addFlash('error', 'Cet email est déjà utilisé par une autre société.');
            }
        }

        return $this->render('society/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
