<?php

namespace App\Security;

use App\Entity\Society;
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

<<<<<<< HEAD
        // Si l'utilisateur est une entreprise
        if ($user instanceof Society) {
            return new RedirectResponse($this->router->generate('society_dashboard'));
        }

        // Si l'utilisateur est un Admin (via la hiérarchie)
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        // Par défaut pour les étudiants
        return new RedirectResponse($this->router->generate('app_reclamation_index'));
    }
}
=======
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_user_home'));
    }
}
>>>>>>> f18e9c9819cf7a44cbe32fa242979d05cfa7ab37
