<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Society;
use App\Service\PdfTextExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/society')]
#[IsGranted('ROLE_SOCIETY')]
final class SocietyRecommendationsController extends AbstractController
{
    #[Route('/recommendations', name: 'society_recommendations', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, PdfTextExtractor $pdfTextExtractor): Response
    {
        /** @var Society $society */
        $society = $this->getUser();

        $min = (float) $request->query->get('min', 0); // 0..1
        $min = max(0.0, min(1.0, $min));

        $qb = $em->createQueryBuilder()
            ->select('c', 'o', 'r')
            ->from(Candidature::class, 'c')
            ->join('c.offre', 'o')
            ->leftJoin('c.recommendation', 'r')
            ->where('o.society = :society')
            ->setParameter('society', $society)
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('c.id', 'DESC');

        /** @var Candidature[] $candidatures */
        $candidatures = $qb->getQuery()->getResult();

        $rows = [];
        foreach ($candidatures as $candidature) {
            $score = $candidature->getRecommendation()?->getScore() ?? 0.0;
            if ($score < $min) {
                continue;
            }

            $offer = $candidature->getOffre();
            $offerKeywords = $this->extractKeywords(($offer?->getTitle() ?? '') . "\n" . ($offer?->getDescription() ?? ''));

            $cvText = '';
            $cvPath = (string) $candidature->getCvPath();
            if ($cvPath !== '') {
                $absolute = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cvs' . DIRECTORY_SEPARATOR . $cvPath;
                $cvText = $pdfTextExtractor->extractText($absolute);
            }

            $matched = $this->matchKeywords($offerKeywords, $cvText);

            $rows[] = [
                'candidature' => $candidature,
                'offer' => $offer,
                'recommendationScore' => $score,
                'keywordScore' => $matched['score'],
                'matchedKeywords' => $matched['keywords'],
            ];
        }

        return $this->render('society/recommendations/index.html.twig', [
            'society' => $society,
            'rows' => $rows,
            'min' => $min,
        ]);
    }

    /**
     * @return string[] unique keywords
     */
    private function extractKeywords(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stop = [
            'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'd', 'et', 'ou', 'a', 'à', 'au', 'aux',
            'pour', 'sur', 'dans', 'en', 'avec', 'sans', 'ce', 'cet', 'cette', 'ces',
        ];

        $keywords = [];
        foreach ($parts as $p) {
            if (mb_strlen($p) < 3) {
                continue;
            }
            if (in_array($p, $stop, true)) {
                continue;
            }
            $keywords[$p] = true;
        }

        return array_keys($keywords);
    }

    /**
     * @param string[] $offerKeywords
     * @return array{score: float, keywords: string[]}
     */
    private function matchKeywords(array $offerKeywords, string $cvText): array
    {
        if ($cvText === '' || count($offerKeywords) === 0) {
            return ['score' => 0.0, 'keywords' => []];
        }

        $cv = mb_strtolower($cvText);
        $matched = [];
        foreach ($offerKeywords as $k) {
            if (mb_stripos($cv, $k) !== false) {
                $matched[] = $k;
            }
        }

        $score = count($matched) / max(1, count($offerKeywords));

        return [
            'score' => $score,
            'keywords' => array_slice($matched, 0, 12),
        ];
    }
}

