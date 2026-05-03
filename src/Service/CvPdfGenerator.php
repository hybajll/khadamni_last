<?php

namespace App\Service;

use App\Entity\Cv;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class CvPdfGenerator
{
    public function __construct(
        private readonly Environment $twig,
        private readonly CvStructuredParser $structuredParser,
        private readonly CvLayoutBuilder $layoutBuilder,
    ) {
    }

    public function downloadResponse(Cv $cv, bool $useImproved = true): Response
    {
        // Use the latest saved version in contenuOriginal.
        // (We don't keep a separate improved version anymore.)
        $content = (string) $cv->getContenuOriginal();
        $sections = $this->structuredParser->parse($content);
        $layout = $this->layoutBuilder->build($sections);

        $photoDataUri = null;
        $photoPath = $cv->getCvPhotoPath();
        if ($photoPath) {
            $absolute = rtrim((string) \dirname(__DIR__, 2), '\\/').'/public/'.ltrim($photoPath, '\\/');
            if (is_file($absolute) && is_readable($absolute)) {
                $ext = strtolower((string) pathinfo($absolute, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    default => null,
                };
                $bin = @file_get_contents($absolute);
                if ($mime && $bin !== false) {
                    $photoDataUri = 'data:'.$mime.';base64,'.base64_encode($bin);
                }
            }
        }

        $html = $this->twig->render('cv/pdf.html.twig', [
            'cv' => $cv,
            'user' => $cv->getUser(),
            'sections' => $sections,
            'layout' => $layout,
            'photo_data_uri' => $photoDataUri,
            'generated_at' => new \DateTimeImmutable(),
            'is_rtl' => $this->looksArabic($content),
        ]);

        // Prefer mPDF when available (best Arabic shaping + RTL support).
        if (class_exists(\Mpdf\Mpdf::class)) {
            return $this->renderWithMpdf($html, $cv);
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $filename = 'cv.pdf';
        if ($cv->getUser()) {
            $name = trim((string) $cv->getUser()->getPrenom().' '.(string) $cv->getUser()->getNom());
            if ($name !== '') {
                $filename = 'CV-'.$name.'.pdf';
                $filename = preg_replace('/[^A-Za-z0-9._\\- ]+/', '', $filename) ?? $filename;
                $filename = str_replace(' ', '_', $filename);
            }
        }

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    private function looksArabic(string $text): bool
    {
        return (preg_match('/\p{Arabic}/u', $text) ?: 0) > 25;
    }

    private function renderWithMpdf(string $html, Cv $cv): Response
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);

        $filename = 'cv.pdf';
        if ($cv->getUser()) {
            $name = trim((string) $cv->getUser()->getPrenom().' '.(string) $cv->getUser()->getNom());
            if ($name !== '') {
                $filename = 'CV-'.$name.'.pdf';
                $filename = preg_replace('/[^A-Za-z0-9._\\- ]+/', '', $filename) ?? $filename;
                $filename = str_replace(' ', '_', $filename);
            }
        }

        return new Response(
            $mpdf->Output($filename, 'S'),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
