<?php

namespace App\Controller;

use App\Entity\Society;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If already authenticated, do not show the login page again.
        // Redirect to the right dashboard based on the user class.
        $current = $this->getUser();
        if ($current instanceof Society) {
            $this->addFlash('info', 'Vous êtes déjà connecté en tant que société.');
            return $this->redirectToRoute('society_dashboard');
        }

        if ($current instanceof User) {
            $this->addFlash('info', 'Vous êtes déjà connecté. Déconnectez-vous pour changer de compte.');
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_admin_dashboard');
            }

            return $this->redirectToRoute('app_user_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

