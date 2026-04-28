<?php

namespace App\Service;

final class CvRecruitmentAnalysisAssistant
{
    public function __construct(
        private readonly LlmClientFactory $llmFactory,
    ) {
    }

    /**
     * @return array{skills: string[], strengths: string[], weaknesses: string[], keywords: string[]}
     */
    public function analyze(string $cvText): array
    {
        $cvText = trim($cvText);
        if ($cvText === '') {
            return [
                'skills' => [],
                'strengths' => [],
                'weaknesses' => [],
                'keywords' => [],
            ];
        }

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                'strengths' => ['type' => 'array', 'items' => ['type' => 'string']],
                'weaknesses' => ['type' => 'array', 'items' => ['type' => 'string']],
                'keywords' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['skills', 'strengths', 'weaknesses', 'keywords'],
        ];

        $prompt = <<<PROMPT
You are an AI specialized in recruitment analysis.

Your task:
1. Analyze the CV content.
2. Extract key skills, technologies, and competencies.
3. Identify strengths and weak points.
4. Provide a list of relevant keywords for job matching.

Rules:
- Do not invent any information.
- Base everything strictly on the CV content.
- Do not guess missing skills or experiences.
- For weaknesses: mention ONLY what is clearly missing or unclear in the CV text (e.g., "dates missing", "no project details"). Do not assume.

Output requirements:
- Return ONLY valid JSON matching the provided JSON schema (no markdown, no code fences).

CV:
{$cvText}
PROMPT;

        $jsonText = $this->llmFactory->client()->generateText(
            $prompt."\n\nJSON Schema:\n".json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $decoded = $this->decodeJson($jsonText);

        return [
            'skills' => $this->normalizeStringList($decoded['skills'] ?? []),
            'strengths' => $this->normalizeStringList($decoded['strengths'] ?? []),
            'weaknesses' => $this->normalizeStringList($decoded['weaknesses'] ?? []),
            'keywords' => $this->normalizeStringList($decoded['keywords'] ?? []),
        ];
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

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $jsonText): array
    {
        $decoded = json_decode($jsonText, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($jsonText, '{');
        $end = strrpos($jsonText, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($jsonText, $start, $end - $start + 1);
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException("Impossible de lire la réponse JSON de l'IA.");
    }
}

