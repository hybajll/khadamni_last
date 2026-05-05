<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Offer;
use App\Entity\Society;
use App\Service\PdfTextExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/society')]
#[IsGranted('ROLE_SOCIETY')]
final class SocietyCandidaturesController extends AbstractController
{
    #[Route('/offers/{id}/candidatures', name: 'society_offer_candidatures', methods: ['GET'])]
    public function list(Offer $offer, EntityManagerInterface $em, PdfTextExtractor $pdfTextExtractor): Response
    {
        /** @var Society $society */
        $society = $this->getUser();

        if ($offer->getSociety() !== $society) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette offre.');
        }

        /** @var Candidature[] $candidatures */
        $candidatures = $em->getRepository(Candidature::class)->findBy(['offre' => $offer]);

        $offerKeywords = $this->extractKeywords($offer->getTitle() . "\n" . $offer->getDescription());

        $rows = [];
        foreach ($candidatures as $candidature) {
            $recommendationScore = $candidature->getRecommendation()?->getScore() ?? 0.0;

            $cvText = '';
            $cvPath = (string) $candidature->getCvPath();
            if ($cvPath !== '') {
                $absolute = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cvs' . DIRECTORY_SEPARATOR . $cvPath;
                $cvText = $pdfTextExtractor->extractText($absolute);
            }

            $matched = $this->matchKeywords($offerKeywords, $cvText);

            $rows[] = [
                'candidature' => $candidature,
                'recommendationScore' => $recommendationScore,
                'keywordScore' => $matched['score'],
                'matchedKeywords' => $matched['keywords'],
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            $cmp = ($b['recommendationScore'] <=> $a['recommendationScore']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return ($b['keywordScore'] <=> $a['keywordScore']);
        });

        return $this->render('society/offer/candidatures.html.twig', [
            'offer' => $offer,
            'rows' => $rows,
            'offerKeywords' => array_slice($offerKeywords, 0, 20),
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

