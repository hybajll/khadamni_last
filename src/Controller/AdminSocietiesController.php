<?php

namespace App\Controller;

use App\Repository\SocietyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
#[Route('/admin/societies')]
final class AdminSocietiesController extends AbstractController
{
    #[Route('', name: 'app_admin_societies', methods: ['GET'])]
    public function index(Request $request, SocietyRepository $societyRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));

        $qb = $societyRepository->createQueryBuilder('s')->orderBy('s.createdAt', 'DESC');
        if ($q !== '') {
            $qb
                ->andWhere('s.name LIKE :q OR s.email LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        return $this->render('admin/societies/index.html.twig', [
            'societies' => $qb->getQuery()->getResult(),
            'q' => $q,
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'app_admin_society_toggle_active', methods: ['POST'])]
    #[IsGranted('ROLE_SUPERADMIN')]
    public function toggleActive(int $id, Request $request, SocietyRepository $societyRepository, EntityManagerInterface $entityManager): Response
    {
        $society = $societyRepository->find($id);
        if (!$society) {
            $this->addFlash('error', 'Société introuvable.');
            return $this->redirectToRoute('app_admin_societies');
        }

        if (!$this->isCsrfTokenValid('toggle_society_'.$society->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_societies');
        }

        $society->setIsActive(!$society->isActive());
        $entityManager->flush();

        $this->addFlash('success', 'Statut société mis à jour.');
        return $this->redirectToRoute('app_admin_societies');
    }

    #[Route('/{id}/delete', name: 'app_admin_society_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPERADMIN')]
    public function delete(int $id, Request $request, SocietyRepository $societyRepository, EntityManagerInterface $entityManager): Response
    {
        $society = $societyRepository->find($id);
        if (!$society) {
            $this->addFlash('error', 'Société introuvable.');
            return $this->redirectToRoute('app_admin_societies');
        }

        if (!$this->isCsrfTokenValid('delete_society_'.$society->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_societies');
        }

        $entityManager->remove($society);
        $entityManager->flush();

        $this->addFlash('success', 'Société supprimée.');
        return $this->redirectToRoute('app_admin_societies');
    }
}
