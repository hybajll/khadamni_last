<?php

namespace App\Controller;

use App\Service\MockArticlesService;
use App\Service\MockJobNewsService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class UserHomeController extends AbstractController
{
    #[Route('/user/home', name: 'app_user_home', methods: ['GET'])]
    public function index(Request $request, MockJobNewsService $mockJobNewsService, MockArticlesService $mockArticlesService): Response
    {
        $lang = strtolower((string) $request->query->get('lang', ''));
        $lang = in_array($lang, ['fr', 'ar'], true) ? $lang : null;

        // Read from env directly (more reliable than params when env is missing).
        $videoEmbedUrl = (string) ($_ENV['MOTIVATIONAL_VIDEO_EMBED_URL'] ?? getenv('MOTIVATIONAL_VIDEO_EMBED_URL') ?? '');
        $videoEmbedUrl = trim($videoEmbedUrl) !== '' ? trim($videoEmbedUrl) : null;

        $videoPath = (string) ($_ENV['MOTIVATIONAL_VIDEO_PATH'] ?? getenv('MOTIVATIONAL_VIDEO_PATH') ?? '');
        $videoPath = trim($videoPath) !== '' ? trim($videoPath) : null;
        $projectDir = rtrim((string) $this->getParameter('kernel.project_dir'), '\\/');
        $publicDir = $projectDir.'/public';

        // If env var is not set (or points to a missing file), auto-pick the first mp4 in public/videos/.
        if ($videoPath !== null) {
            $publicVideoAbsolutePath = $publicDir.'/' . ltrim($videoPath, '\\/');
            if (!is_file($publicVideoAbsolutePath)) {
                $videoPath = null;
            }
        }

        // Prefer local mp4 if available; YouTube embeds can be blocked.
        if ($videoPath === null) {
            $videosDir = $publicDir.'/videos';
            if (is_dir($videosDir)) {
                $files = glob($videosDir.'/*.mp4') ?: [];
                if (count($files) > 0) {
                    $pickedAbsolute = $files[0];
                    $pickedName = basename($pickedAbsolute);

                    // Some servers have trouble with non-ascii/space-heavy filenames. Create a safe copy if needed.
                    $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $pickedName) ?? $pickedName;
                    $safeName = trim((string) $safeName, '-');
                    if ($safeName === '' || $safeName === $pickedName) {
                        $videoPath = 'videos/'.$pickedName;
                    } else {
                        $safeAbsolute = $videosDir.'/'.$safeName;
                        if (!is_file($safeAbsolute)) {
                            (new Filesystem())->copy($pickedAbsolute, $safeAbsolute, true);
                        }
                        $videoPath = 'videos/'.$safeName;
                    }
                }
            }
        }

        // Normalize common YouTube URLs to an embed URL (only used when local mp4 not available).
        if ($videoPath !== null) {
            $videoEmbedUrl = null;
        } elseif ($videoEmbedUrl !== null) {
            $videoEmbedUrl = $this->normalizeYoutubeEmbedUrl($videoEmbedUrl);
            if ($videoEmbedUrl !== null && !preg_match('#^https?://#i', $videoEmbedUrl)) {
                $videoEmbedUrl = null;
            }
        }

        return $this->render('user/home.html.twig', [
            'user' => $this->getUser(),
            'news' => $mockJobNewsService->getLatest(),
            'articles' => $mockArticlesService->getLatest(),
            'articles_lang' => $lang,
            'video_embed_url' => $videoEmbedUrl,
            'video_path' => $videoPath,
        ]);
    }

    private function normalizeYoutubeEmbedUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // Already an embed URL.
        if (preg_match('#^https?://(www\\.)?youtube\\.com/embed/([^?&/]+)#i', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[2].'?rel=0&modestbranding=1';
        }

        // youtu.be/VIDEO_ID
        if (preg_match('#^https?://youtu\\.be/([^?&/]+)#i', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1].'?rel=0&modestbranding=1';
        }

        // youtube.com/watch?v=VIDEO_ID
        if (preg_match('#^https?://(www\\.)?youtube\\.com/watch\\?v=([^?&/]+)#i', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[2].'?rel=0&modestbranding=1';
        }

        return $url;
    }
}
