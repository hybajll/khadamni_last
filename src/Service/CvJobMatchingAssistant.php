<?php

namespace App\Service;

use App\Entity\Offer;

final class CvJobMatchingAssistant
{
    public function __construct(
        private readonly LlmClientFactory $llmFactory,
    ) {
    }

    /**
     * @param Offer[] $offers
     * @return array<int, array{offer_id: int|null, job_title: string, score: int, matching_skills: string[], missing_skills: string[], reason: string}>
     */
    public function match(string $cvText, array $offers): array
    {
        $cvText = trim($cvText);
        if ($cvText === '' || $offers === []) {
            return [];
        }

        try {
            return $this->matchWithApi($cvText, $offers);
        } catch (\Throwable $e) {
            // If the provider is temporarily unavailable (quota/high demand/network),
            // fall back to a local keyword overlap scoring to avoid a 500 error.
            return $this->matchLocally($cvText, $offers);
        }
    }

    /**
     * @param Offer[] $offers
     * @return array<int, array{offer_id: int|null, job_title: string, score: int, matching_skills: string[], missing_skills: string[], reason: string}>
     */
    private function matchWithApi(string $cvText, array $offers): array
    {
        $jobList = array_map(static function (Offer $offer): array {
            return [
                'id' => $offer->getId(),
                'job_title' => (string) $offer->getTitle(),
                'description' => (string) $offer->getDescription(),
                'domain' => $offer->getDomain(),
                'contract_type' => $offer->getContractType(),
                'experience_level' => $offer->getExperienceLevel(),
                'location' => $offer->getLocation(),
            ];
        }, $offers);

        $schema = [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'offer_id' => ['type' => ['integer', 'null']],
                    'job_title' => ['type' => 'string'],
                    'score' => ['type' => 'integer'],
                    'matching_skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'missing_skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'reason' => ['type' => 'string'],
                ],
                'required' => ['offer_id', 'job_title', 'score', 'matching_skills', 'missing_skills', 'reason'],
            ],
        ];

        $prompt = <<<PROMPT
You are an AI job matching assistant.

Your task:
- Compare a candidate CV with multiple job offers.
- Calculate a compatibility score for each offer.
- Identify which offers best match the candidate profile.

Rules:
- Only use information present in the CV.
- Do not assume missing skills.
- Match based on skills, technologies, and experience explicitly stated.

For each job offer:
- Give a compatibility score (0–100 as an integer)
- Explain briefly why
- Highlight matching keywords
- If you include missing skills, list ONLY skills that appear in the offer but are not in the CV (no guessing).

Output requirements:
- Return ONLY valid JSON matching the provided JSON schema (no markdown, no code fences).
- Set offer_id to the id from the job list input.

CV:
{$cvText}

Job Offers (JSON):
{$this->json($jobList)}
PROMPT;

        $jsonText = $this->llmFactory->client()->generateText(
            $prompt."\n\nJSON Schema:\n".json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $decoded = $this->decodeJson($jsonText);
        if (!is_array($decoded)) {
            throw new \RuntimeException("Réponse IA invalide (liste attendue).");
        }

        $results = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $score = (int) ($row['score'] ?? 0);
            $score = max(0, min(100, $score));

            $results[] = [
                'offer_id' => isset($row['offer_id']) ? (is_int($row['offer_id']) ? $row['offer_id'] : null) : null,
                'job_title' => trim((string) ($row['job_title'] ?? '')),
                'score' => $score,
                'matching_skills' => $this->normalizeStringList($row['matching_skills'] ?? []),
                'missing_skills' => $this->normalizeStringList($row['missing_skills'] ?? []),
                'reason' => trim((string) ($row['reason'] ?? '')),
            ];
        }

        usort($results, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * Local fallback (no external API).
     *
     * @param Offer[] $offers
     * @return array<int, array{offer_id: int|null, job_title: string, score: int, matching_skills: string[], missing_skills: string[], reason: string}>
     */
    private function matchLocally(string $cvText, array $offers): array
    {
        $cvTokens = $this->extractTokens($cvText);
        if ($cvTokens === []) {
            return [];
        }

        $results = [];
        foreach ($offers as $offer) {
            $offerText = implode("\n", array_filter([
                (string) $offer->getTitle(),
                (string) $offer->getDescription(),
                (string) ($offer->getDomain() ?? ''),
                (string) ($offer->getContractType() ?? ''),
                (string) ($offer->getExperienceLevel() ?? ''),
                (string) ($offer->getLocation() ?? ''),
            ]));

            $offerTokens = $this->extractTokens($offerText);
            if ($offerTokens === []) {
                $overlap = [];
            } else {
                $overlap = array_values(array_intersect($cvTokens, $offerTokens));
            }

            $offerSize = max(1, count($offerTokens));
            $cvSize = max(1, count($cvTokens));
            $overlapCount = count($overlap);

            // Simple overlap ratio with a slight boost (so small overlaps still look meaningful).
            $ratio = $overlapCount / min($offerSize, $cvSize);
            $score = (int) round(min(1.0, $ratio * 1.35) * 100);

            $results[] = [
                'offer_id' => $offer->getId(),
                'job_title' => (string) $offer->getTitle(),
                'score' => $score,
                'matching_skills' => array_slice($overlap, 0, 10),
                'missing_skills' => [],
                'reason' => $overlapCount > 0
                    ? 'Matching simplifié (hors IA) basé sur des mots-clés communs.'
                    : 'Matching simplifié (hors IA) : peu de mots-clés communs détectés.',
            ];
        }

        usort($results, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * Extract normalized tokens (skills/keywords) from text.
     *
     * @return string[]
     */
    private function extractTokens(string $text): array
    {
        $text = mb_strtolower($text);

        // Try to remove accents for better matching (best-effort).
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($ascii) && $ascii !== '') {
            $text = $ascii;
        }

        $text = preg_replace('/[^a-z0-9+#.\\s]+/i', ' ', $text) ?? $text;
        $parts = preg_split('/\\s+/', trim($text)) ?: [];

        $stop = [
            'avec', 'pour', 'dans', 'les', 'des', 'une', 'un', 'et', 'ou', 'de', 'du', 'la', 'le',
            'the', 'and', 'or', 'to', 'in', 'of', 'a', 'an',
        ];

        $tokens = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '' || strlen($p) < 3) {
                continue;
            }
            if (in_array($p, $stop, true)) {
                continue;
            }
            $tokens[] = $p;
        }

        // Always keep common tech tokens even if short.
        $techKeep = ['c', 'c++', 'c#', 'php', 'sql', 'git', 'api', 'html', 'css', 'js', 'java'];
        foreach ($techKeep as $t) {
            if (str_contains($text, $t)) {
                $tokens[] = $t;
            }
        }

        $tokens = array_values(array_unique($tokens));

        // Limit to a sane number.
        return array_slice($tokens, 0, 250);
    }

    private function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    private function normalizeStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            $items[] = $item;
        }

        return array_values(array_unique($items));
    }

    private function decodeJson(string $jsonText): mixed
    {
        $decoded = json_decode($jsonText, true);
        if ($decoded !== null) {
            return $decoded;
        }

        $start = strpos($jsonText, '[');
        $end = strrpos($jsonText, ']');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($jsonText, $start, $end - $start + 1);
            $decoded = json_decode($candidate, true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        $start = strpos($jsonText, '{');
        $end = strrpos($jsonText, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($jsonText, $start, $end - $start + 1);
            $decoded = json_decode($candidate, true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        throw new \RuntimeException("Impossible de lire la réponse JSON de l'IA.");
    }
}
