<?php

namespace App\Security;

use App\Entity\Society;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        // 1. Priorité aux Sociétés (Votre code)
        if ($user instanceof Society) {
            return new RedirectResponse($this->router->generate('society_dashboard'));
        }

        // 2. Priorité aux Admins (Code de vos collègues, sécurisé avec check instance)
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        // 3. Par défaut pour les autres types d'utilisateurs (Candidats/Étudiants)
        // Vous pouvez choisir 'app_reclamation_index' ou 'app_user_home' selon votre besoin final.
        return new RedirectResponse($this->router->generate('app_user_home'));
    }
}