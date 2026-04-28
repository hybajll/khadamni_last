<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Form\AdminType;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPERADMIN')]
#[Route('/legacy/admins')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_admins', methods: ['GET'])]
    public function index(AdminRepository $adminRepository, Request $request): Response
    {
        $q = $request->query->get('q');
        
        // Si une recherche est lancée
        if ($q) {
            $admins = $adminRepository->createQueryBuilder('a')
                ->where('a.email LIKE :q')
                ->orWhere('a.adminRole LIKE :q')
                ->setParameter('q', '%'.$q.'%')
                ->orderBy('a.id', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            $admins = $adminRepository->findBy([], ['id' => 'DESC']);
        }

        return $this->render('admin/admins.html.twig', [
            'admins' => $admins,
            'q' => $q
        ]);
    }

    #[Route('/new', name: 'app_admin_admins_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $admin = new Admin();
        $form = $this->createForm(AdminType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            $admin
                ->setPassword(password_hash($plainPassword, PASSWORD_BCRYPT))
                ->setActif(true)
                ->setLocalDateTime(new \DateTimeImmutable());

            $entityManager->persist($admin);
            $entityManager->flush();

            $this->addFlash('success', 'Administrateur créé avec succès.');
            return $this->redirectToRoute('app_admin_admins');
        }

        return $this->render('admin/new.html.twig', [
            'admin' => $admin,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Admin $admin, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminType::class, $admin, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            if ($plainPassword !== '') {
                $admin->setPassword(password_hash($plainPassword, PASSWORD_BCRYPT));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Administrateur modifié avec succès.');
            return $this->redirectToRoute('app_admin_admins');
        }

        return $this->render('admin/edit.html.twig', [
            'admin' => $admin,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Admin $admin, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_admin_'.$admin->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($admin);
            $entityManager->flush();
            $this->addFlash('success', 'Administrateur supprimé.');
        }
        return $this->redirectToRoute('app_admin_admins');
    }
}