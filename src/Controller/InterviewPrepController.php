<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use App\Service\InterviewAiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class InterviewPrepController extends AbstractController
{
    #[Route('/interview/prep', name: 'app_interview_prep', methods: ['GET'])]
    public function prep(Request $request, OfferRepository $offerRepository): Response
    {
        $offerId = $request->query->getInt('offerId', 0);
        /** @var Offer|null $selectedOffer */
        $selectedOffer = $offerId > 0 ? $offerRepository->find($offerId) : null;

        $offers = $offerRepository->findBy(
            ['isActive' => true],
            ['createdAt' => 'DESC'],
            50,
        );

        $questions = $this->buildQuestions($selectedOffer);

        return $this->render('interview/prep.html.twig', [
            'offers' => $offers,
            'selectedOffer' => $selectedOffer,
            'questions' => $questions,
        ]);
    }

    #[Route('/interview/prep/ai/start', name: 'app_interview_prep_ai_start', methods: ['POST'])]
    public function aiStart(Request $request, OfferRepository $offerRepository, InterviewAiService $ai, SessionInterface $session): JsonResponse
    {
        $payload = $request->toArray();
        $offerId = isset($payload['offerId']) ? (int) $payload['offerId'] : 0;
        /** @var Offer|null $offer */
        $offer = $offerId > 0 ? $offerRepository->find($offerId) : null;

        try {
            $generated = $ai->generateQuestions($offer);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        $session->set('interview.offerContext', $generated['offerContext']);
        $session->set('interview.questions', $generated['questions']);
        $session->set('interview.index', 0);

        $first = $generated['questions'][0] ?? null;

        return new JsonResponse([
            'offerContext' => $generated['offerContext'],
            'questions' => $generated['questions'],
            'currentIndex' => 0,
            'question' => $first,
        ]);
    }

    #[Route('/interview/prep/ai/answer', name: 'app_interview_prep_ai_answer', methods: ['POST'])]
    public function aiAnswer(Request $request, InterviewAiService $ai, SessionInterface $session): JsonResponse
    {
        $payload = $request->toArray();
        $answer = isset($payload['answer']) && is_string($payload['answer']) ? trim($payload['answer']) : '';
        $index = isset($payload['index']) ? (int) $payload['index'] : (int) $session->get('interview.index', 0);

        $offerContext = $session->get('interview.offerContext', []);
        $questions = $session->get('interview.questions', []);

        if (!is_array($offerContext) || !is_array($questions)) {
            return new JsonResponse(['error' => 'Session not initialized. Call /ai/start first.'], 400);
        }

        $question = $questions[$index] ?? null;
        if (!is_array($question)) {
            return new JsonResponse(['error' => 'Unknown question index.'], 400);
        }

        if ($answer === '') {
            return new JsonResponse(['error' => 'Empty answer.'], 400);
        }

        try {
            $eval = $ai->evaluate($offerContext, $question, $answer);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        // Keep session index in sync with the last evaluated question.
        $session->set('interview.index', $index);

        return new JsonResponse([
            'evaluation' => $eval,
        ]);
    }

    #[Route('/interview/prep/ai/perfect', name: 'app_interview_prep_ai_perfect', methods: ['POST'])]
    public function aiPerfect(Request $request, InterviewAiService $ai, SessionInterface $session): JsonResponse
    {
        $payload = $request->toArray();
        $index = isset($payload['index']) ? (int) $payload['index'] : (int) $session->get('interview.index', 0);

        $offerContext = $session->get('interview.offerContext', []);
        $questions = $session->get('interview.questions', []);

        if (!is_array($offerContext) || !is_array($questions)) {
            return new JsonResponse(['error' => 'Session not initialized. Call /ai/start first.'], 400);
        }

        $question = $questions[$index] ?? null;
        if (!is_array($question)) {
            return new JsonResponse(['error' => 'Unknown question index.'], 400);
        }

        try {
            $perfect = $ai->perfectAnswer($offerContext, $question);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        return new JsonResponse([
            'perfectAnswer' => $perfect,
        ]);
    }

    // Kept for future audio mode (not used in text-only UX).
    #[Route('/interview/prep/upload-audio', name: 'app_interview_prep_upload_audio', methods: ['POST'])]
    public function uploadAudio(Request $request): JsonResponse
    {
        $file = $request->files->get('audio');
        if (!$file) {
            return new JsonResponse(['error' => 'Missing audio file'], 400);
        }

        $projectDir = rtrim((string) $this->getParameter('kernel.project_dir'), '\\/');
        $targetDir = $projectDir.'/public/uploads/interview-audio';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        $mime = (string) ($file->getMimeType() ?? '');
        if ($mime !== '' && !str_starts_with($mime, 'audio/') && $mime !== 'application/octet-stream') {
            return new JsonResponse(['error' => 'Invalid file type'], 400);
        }

        $ext = strtolower((string) ($file->guessExtension() ?: 'webm'));
        $allowedExt = ['webm', 'ogg', 'wav', 'mp3', 'm4a'];
        if (!in_array($ext, $allowedExt, true)) {
            $ext = 'webm';
        }

        $name = 'ans-'.bin2hex(random_bytes(8)).'.'.$ext;
        $file->move($targetDir, $name);

        return new JsonResponse([
            'url' => '/uploads/interview-audio/'.$name,
            'name' => $name,
        ]);
    }

    #[Route('/interview/prep/transcribe', name: 'app_interview_prep_transcribe', methods: ['POST'])]
    public function transcribe(Request $request): JsonResponse
    {
        $file = $request->files->get('audio');
        if (!$file) {
            return new JsonResponse(['error' => 'Missing audio file'], 400);
        }

        $projectDir = rtrim((string) $this->getParameter('kernel.project_dir'), '\\/');
        $workDir = $projectDir.'/var/interview';
        if (!is_dir($workDir)) {
            @mkdir($workDir, 0775, true);
        }

        $ext = $file->guessExtension() ?: 'webm';
        $base = $workDir.'/audio-'.bin2hex(random_bytes(8));
        $inputPath = $base.'.'.$ext;
        $wavPath = $base.'.wav';
        $outBase = $base.'-whisper';
        $outTxt = $outBase.'.txt';

        $file->move($workDir, basename($inputPath));

        $ffmpeg = $this->resolveToolPath((string) ($_ENV['FFMPEG_BIN'] ?? getenv('FFMPEG_BIN') ?? 'ffmpeg'), $projectDir);
        $whisperBin = $this->resolveToolPath((string) ($_ENV['WHISPER_BIN'] ?? getenv('WHISPER_BIN') ?? ''), $projectDir);
        $whisperModel = $this->resolveToolPath((string) ($_ENV['WHISPER_MODEL'] ?? getenv('WHISPER_MODEL') ?? ''), $projectDir);

        // Some whisper.cpp releases ship a tiny deprecated main.exe that only prints a warning.
        // Prefer whisper-cli.exe automatically when present next to main.exe.
        if ($whisperBin !== '' && str_ends_with(strtolower($whisperBin), 'main.exe')) {
            $cli = \dirname($whisperBin).DIRECTORY_SEPARATOR.'whisper-cli.exe';
            if (is_file($cli)) {
                $whisperBin = $cli;
            }
        }

        if (trim($whisperBin) === '' || trim($whisperModel) === '') {
            return new JsonResponse([
                'error' => 'Server STT not configured',
                'hint' => 'Set env vars: FFMPEG_BIN, WHISPER_BIN, WHISPER_MODEL (relative paths from project root are OK).',
            ], 500);
        }

        $ffmpegCmd = sprintf(
            '%s -y -i %s -ac 1 -ar 16000 -vn %s',
            escapeshellarg($ffmpeg),
            escapeshellarg($inputPath),
            escapeshellarg($wavPath),
        );
        $ffmpegOut = $this->runCommand($ffmpegCmd);
        if (!is_file($wavPath)) {
            return new JsonResponse([
                'error' => 'ffmpeg conversion failed',
                'details' => $ffmpegOut,
            ], 500);
        }

        $whisperCmd = sprintf(
            '%s -m %s -f %s -l fr -nt -otxt -of %s',
            escapeshellarg($whisperBin),
            escapeshellarg($whisperModel),
            escapeshellarg($wavPath),
            escapeshellarg($outBase),
        );
        $whisperOut = $this->runCommand($whisperCmd);

        $text = '';
        if (is_file($outTxt)) {
            $text = trim((string) @file_get_contents($outTxt));
        }

        if ($text === '') {
            return new JsonResponse([
                'error' => 'transcription failed',
                'details' => $whisperOut,
            ], 500);
        }

        return new JsonResponse(['text' => $text]);
    }

    /**
     * @return array<int, array{id:string,type:string,text:string}>
     */
    private function buildQuestions(?Offer $offer): array
    {
        $baseLabel = 'ce poste';
        $base = [
            ['id' => 'intro_agent', 'type' => 'intro', 'text' => "Bonjour, je suis votre recruteur. Je vais d’abord vous poser quelques questions générales, puis des questions techniques. Cliquez sur « Question suivante » pour commencer."],
            ['id' => 'presenter', 'type' => 'general', 'text' => "Présentez-vous en 60 secondes pour $baseLabel."],
            ['id' => 'motivation', 'type' => 'general', 'text' => 'Pourquoi ce poste vous intéresse ? Donnez 2 raisons concrètes + 1 preuve.'],
            ['id' => 'fit', 'type' => 'general', 'text' => "Qu'est-ce qui vous différencie des autres candidats pour ce poste ? (1 exemple + 1 résultat chiffré)"],
            // Technical block
            ['id' => 'tech1', 'type' => 'technical', 'text' => 'Expliquez un concept technique clé de votre domaine et comment vous l’avez appliqué.'],
            ['id' => 'tech2', 'type' => 'technical', 'text' => 'Décrivez un bug/problème difficile : comment l’avez-vous diagnostiqué et corrigé ?'],
            ['id' => 'tech3', 'type' => 'technical', 'text' => 'Comment assurez-vous la qualité (tests, code review, CI) ? Donnez un exemple.'],
            ['id' => 'tech4', 'type' => 'technical', 'text' => 'Parlez d’une optimisation (perf, coût, UX). Quel impact mesurable ?'],
            ['id' => 'tech5', 'type' => 'technical', 'text' => 'Sécurité : quelles bonnes pratiques appliquez-vous dans vos projets ?'],
            // Senior block
            ['id' => 'senior1', 'type' => 'senior', 'text' => 'Architecture : comment concevriez-vous une solution robuste pour ce poste ? (choix + compromis)'],
            ['id' => 'senior2', 'type' => 'senior', 'text' => 'Scalabilité : comment gérer la montée en charge ? (cache, DB, files, monitoring)'],
            ['id' => 'senior3', 'type' => 'senior', 'text' => 'Décisions : racontez une décision technique difficile et comment vous l’avez justifiée.'],
            ['id' => 'closing', 'type' => 'closing', 'text' => 'Quelles questions pertinentes poseriez-vous au recruteur ? (2 questions)'],
        ];

        if (!$offer) {
            return $base;
        }

        $title = trim((string) $offer->getTitle());
        $domain = trim((string) ($offer->getDomain() ?? ''));
        $location = trim((string) ($offer->getLocation() ?? ''));
        $contract = trim((string) ($offer->getContractType() ?? ''));
        $level = trim((string) ($offer->getExperienceLevel() ?? ''));

        $context = $title;
        if ($domain !== '') {
            $context .= " ($domain)";
        }
        if ($location !== '') {
            $context .= " — $location";
        }

        $label = $context !== '' ? $context : $title;

        return [
            ['id' => 'intro_agent', 'type' => 'intro', 'text' => "Bonjour, je suis votre recruteur. Je vais d’abord vous poser quelques questions générales, puis des questions techniques liées à l’offre. Cliquez sur « Question suivante » pour commencer."],
            // 3 general
            ['id' => 'presenter', 'type' => 'general', 'text' => "Présentez-vous en 60 secondes pour le poste : $label."],
            ['id' => 'motivation', 'type' => 'general', 'text' => "Pourquoi ce poste \"$title\" vous intéresse ? Donnez 2 raisons concrètes + 1 preuve."],
            ['id' => 'fit', 'type' => 'general', 'text' => "Qu'est-ce qui vous différencie pour \"$title\" ? (1 exemple + 1 résultat chiffré)"],
            // technical (offer-tied)
            ['id' => 'tech_stack', 'type' => 'technical', 'text' => "Quelles technologies de votre stack sont les plus pertinentes pour \"$title\" et pourquoi ?"],
            ['id' => 'tech_project', 'type' => 'technical', 'text' => "Décrivez un projet technique proche de \"$title\" : votre rôle, choix techniques, impact chiffré."],
            ['id' => 'tech_problem', 'type' => 'technical', 'text' => "Donnez un exemple de bug/problème difficile (diagnostic → solution) lié à ce type de poste."],
            ['id' => 'tech_quality', 'type' => 'technical', 'text' => "Qualité : tests, CI/CD, revue de code — comment vous organisez ça ? Exemple concret."],
            ['id' => 'tech_security', 'type' => 'technical', 'text' => "Sécurité : quels risques voyez-vous sur ce type de poste et comment vous les réduisez ?"],
            // senior
            ['id' => 'senior_arch', 'type' => 'senior', 'text' => "Architecture : proposez une mini-architecture pour un besoin typique du poste (choix + compromis)."],
            ['id' => 'senior_scale', 'type' => 'senior', 'text' => "Scalabilité/perf : comment gérer la montée en charge ? (cache, DB, files, monitoring)"],
            ['id' => 'senior_tradeoffs', 'type' => 'senior', 'text' => "Décision senior : racontez une décision technique difficile (trade-off) et comment vous l’avez défendue."],
            // closing
            ['id' => 'closing', 'type' => 'closing', 'text' => "Posez 2 questions intelligentes sur l’équipe, les objectifs et les attentes du poste."],
        ];
    }

    private function resolveToolPath(string $value, string $projectDir): string
    {
        $path = trim($value);
        if ($path === '') {
            return '';
        }

        if (!str_contains($path, '/') && !str_contains($path, '\\') && !preg_match('/^[A-Za-z]:$/', $path)) {
            return $path;
        }

        if (!preg_match('#^[A-Za-z]:[\\\\/]#', $path) && !str_starts_with($path, '/') && !str_starts_with($path, '\\\\')) {
            $path = $projectDir.'/'.ltrim($path, '\\/');
        }

        return $path;
    }

    private function runCommand(string $cmd): string
    {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = @proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($proc)) {
            return 'Failed to start process.';
        }

        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        @fclose($pipes[1]);
        @fclose($pipes[2]);
        @proc_close($proc);

        return trim($stdout."\n".$stderr);
    }
}
