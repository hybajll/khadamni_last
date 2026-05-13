<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\News;
use App\Form\NewsType;
use App\Repository\NewsRepository;
use App\Service\ScrapingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/news')]
final class NewsController extends AbstractController
{
    #[Route(name: 'app_news_index', methods: ['GET', 'POST'])]
    public function index(Request $request, NewsRepository $newsRepository, ScrapingService $scrapingService, EntityManagerInterface $entityManager, KernelInterface $kernel, SluggerInterface $slugger): Response
    {
        // 1. Récupérer toutes les news de la base de données, triées par date décroissante
        $databaseNews = $newsRepository->findBy([], ['createdAt' => 'DESC']);

        // 2. Appeler le ScrapingService pour récupérer les offres web
        try {
            $scrapedNews = $scrapingService->scrapeKeejob();
        } catch (\Exception $e) {
            // En cas d'erreur de scraping, continuer avec une liste vide
            $scrapedNews = [];
        }

        // 3. Fusionner les deux listes
        $allNews = array_merge($scrapedNews, $databaseNews);

        // 4. Préparer une nouvelle instance de News et son formulaire pour la création
        $newNews = new News();
        $form = $this->createForm(NewsType::class, $newNews);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de fichier
            $imageFile = $form->get('imageUrl')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $uploadsDir = $kernel->getProjectDir() . '/public/uploads/news';
                    
                    // Créer le répertoire s'il n'existe pas
                    $filesystem = new Filesystem();
                    if (!$filesystem->exists($uploadsDir)) {
                        $filesystem->mkdir($uploadsDir, 0755);
                    }

                    $imageFile->move($uploadsDir, $newFilename);
                    $newNews->setImageUrl('/uploads/news/' . $newFilename);
                } catch (\Exception $e) {
                    // En cas d'erreur d'upload, continuer sans image
                    $newNews->setImageUrl(null);
                }
            }

            // Injecter les données automatiquement
            $newNews->setCreatedAt(new \DateTimeImmutable());
            $newNews->setIsExternal(false);
            if ($this->getUser()) {
                $newNews->setUser($this->getUser());
            }
            
            $entityManager->persist($newNews);
            $entityManager->flush();

            return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
        }

        // 5. Envoyer à la vue Twig
        return $this->render('news/index.html.twig', [
            'allNews' => $allNews,
            'form' => $form,
        ]);
    }

    #[Route('/new', name: 'app_news_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($news);
            $entityManager->flush();

            return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('news/new.html.twig', [
            'news' => $news,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_news_show', methods: ['GET'])]
    public function show(News $news): Response
    {
        return $this->render('news/show.html.twig', [
            'news' => $news,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_news_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, News $news, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('news/edit.html.twig', [
            'news' => $news,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_news_delete', methods: ['POST'])]
    public function delete(Request $request, News $news, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$news->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($news);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/like', name: 'app_news_like', methods: ['POST'])]
    public function likeNews(News $news, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($news->getLikedByUsers()->contains($user)) {
            // Unlike: retirer l'utilisateur et décrémenter
            $news->removeLikedByUser($user);
            $news->setLikes(max(0, ($news->getLikes() ?? 0) - 1));
        } else {
            // Like: ajouter l'utilisateur et incrémenter
            $news->addLikedByUser($user);
            $news->setLikes(($news->getLikes() ?? 0) + 1);
        }

        $em->persist($news);
        $em->flush();

        return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/comment/{id}/like', name: 'app_comment_like', methods: ['POST'])]
    public function likeComment(Comment $comment, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($comment->getLikedByUsers()->contains($user)) {
            // Unlike: retirer l'utilisateur et décrémenter
            $comment->removeLikedByUser($user);
            $comment->setLikes(max(0, ($comment->getLikes() ?? 0) - 1));
        } else {
            // Like: ajouter l'utilisateur et incrémenter
            $comment->addLikedByUser($user);
            $comment->setLikes(($comment->getLikes() ?? 0) + 1);
        }

        $em->persist($comment);
        $em->flush();

        return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/comment', name: 'app_news_add_comment', methods: ['POST'])]
    public function addComment(Request $request, News $news, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $content = $request->request->get('comment_content');

        if (!$content || empty(trim($content))) {
            return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
        }

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setNews($news);
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setLikes(0);

        $em->persist($comment);
        $em->flush();

        return $this->redirectToRoute('app_news_index', [], Response::HTTP_SEE_OTHER);
    }
}
