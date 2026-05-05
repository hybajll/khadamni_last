<?php

namespace App\Controller;

use App\Entity\Society;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/society')]
final class SocietySecurityController extends AbstractController
{
    #[Route('/login', name: 'society_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If already authenticated, do not show the login page again.
        // Redirect based on the user class.
        $current = $this->getUser();
        if ($current instanceof Society) {
            $this->addFlash('info', 'Vous êtes déjà connecté en tant que société.');
            return $this->redirectToRoute('society_dashboard');
        }

        if ($current instanceof User) {
            $this->addFlash('info', 'Vous êtes déjà connecté en tant que candidat.');
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
            return $this->redirectToRoute('app_user_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastEmail = $authenticationUtils->getLastUsername();

        return $this->render('society/login.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'society_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

