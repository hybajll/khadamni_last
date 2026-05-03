<?php

namespace App\Service;

final class CvAiApiAssistant
{
    public function __construct(
        private readonly LlmClientFactory $llmFactory,
    ) {
    }

    /**
     * @param ?string $targetLang null = keep original language, otherwise: fr|en
     */
    public function improveAndAdvise(string $originalText, ?string $targetLang = null): CvAiResult
    {
        $clean = trim($originalText);
        if ($clean === '') {
            return new CvAiResult('', '');
        }

        try {
            return $this->improveWithApi($clean, $targetLang);
        } catch (\Throwable) {
            return $this->improveLocally($clean, $targetLang);
        }
    }

    private function improveWithApi(string $cvText, ?string $targetLang): CvAiResult
    {
        $prompt = $this->buildPrompt($cvText, $targetLang);

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'improved_cv' => ['type' => 'string'],
                'advice' => ['type' => 'string'],
                'detected_skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                'strengths' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['improved_cv', 'advice', 'detected_skills', 'strengths'],
        ];

        $jsonText = $this->llmFactory->client()->generateText(
            $prompt."\n\nJSON Schema:\n".json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $decoded = $this->decodeJson($jsonText);

        $improved = trim((string) ($decoded['improved_cv'] ?? ''));
        if ($improved === '') {
            return new CvAiResult('', '');
        }

        $adviceParts = [];

        $advice = trim((string) ($decoded['advice'] ?? ''));
        if ($advice !== '') {
            $adviceParts[] = $advice;
        }

        $skills = $decoded['detected_skills'] ?? [];
        if (is_array($skills) && $skills !== []) {
            $skills = array_values(array_filter(array_map('trim', $skills), static fn (string $s): bool => $s !== ''));
            if ($skills !== []) {
                $adviceParts[] = "Compétences détectées :\n- ".implode("\n- ", array_slice($skills, 0, 12));
            }
        }

        $strengths = $decoded['strengths'] ?? [];
        if (is_array($strengths) && $strengths !== []) {
            $strengths = array_values(array_filter(array_map('trim', $strengths), static fn (string $s): bool => $s !== ''));
            if ($strengths !== []) {
                $adviceParts[] = "Points forts :\n- ".implode("\n- ", array_slice($strengths, 0, 10));
            }
        }

        return new CvAiResult($improved, implode("\n\n", $adviceParts));
    }

    private function improveLocally(string $cvText, ?string $targetLang): CvAiResult
    {
        $clean = str_replace(["\r\n", "\r"], "\n", $cvText);
        $clean = preg_replace("/\n{3,}/", "\n\n", $clean) ?? $clean;

        $lines = array_values(array_filter(array_map('trim', explode("\n", $clean)), static fn (string $l): bool => $l !== ''));
        $items = array_map(static fn (string $l): string => ltrim($l, "•-* \t"), $lines);
        $items = array_values(array_filter($items, static fn (string $l): bool => $l !== ''));

        $improved = "CONTENU\n".implode("\n", array_map(static fn (string $i): string => '- '.$i, $items));

        $advice = "- Conseil : ajoutez des dates et des chiffres (durée, outils, résultats) si possible.";
        if ($targetLang !== null) {
            $advice .= "\n- Note : la traduction est disponible uniquement quand l’API IA est accessible.";
        }

        return new CvAiResult(trim($improved), $advice);
    }

    private function buildPrompt(string $cvText, ?string $targetLang): string
    {
        $languageHint = $this->detectLanguageHint($cvText);
        $target = $targetLang ? strtoupper($targetLang) : 'SAME_AS_ORIGINAL';

        $translationRules = $targetLang === null
            ? "Keep the SAME language(s) as the original CV. Do NOT translate."
            : "Rewrite and translate the CV into the target language: {$target}. Keep it consistent (French OR English only).";

        return <<<PROMPT
You are an AI assistant specialized in CV analysis and optimization.

Your task is to improve and restructure a CV while strictly respecting the following rules:
1. You MUST NOT add any information that is not present in the original CV.
2. You MUST NOT invent experiences, skills, or achievements.
3. You MUST only reformulate, correct, and structure the existing content.
4. Improve grammar, clarity, and professional tone.
5. Organize the CV into clear sections (Profile, Education, Experience, Skills, Projects, etc.) in the CV language.
6. Highlight strengths based only on the provided content.
7. Use concise and impactful language suitable for recruiters.

Additionally:
* Detect key skills and competencies from the CV.
* Identify strong points and suggest better phrasing.
* Keep the content truthful and realistic.

Language hint: {$languageHint}
Target language: {$target}

Output requirements:
* Return ONLY valid JSON that matches the provided JSON schema. No markdown, no code fences.
* The field "improved_cv" must contain ONLY the improved CV (no explanations).
* Put advice/suggestions into "advice", and lists into the other fields.
* {$translationRules}
* If target language is French: output French and use French section titles.
* If target language is English: output English and use English section titles.
* Do not invent any information, even while translating.
* Formatting for improved_cv (plain text):
  - Use clear section titles on their own line.
  - Use bullet points starting with "- " (dash + space).
  - Avoid long paragraphs.
  - In "Profile"/"Profil"/Arabic summary: maximum 4 bullets.
  - Keep layout clean and recruiter-friendly.

Here is the CV content:
{$cvText}
PROMPT;
    }

    private function detectLanguageHint(string $text): string
    {
        $arabic = preg_match_all('/\p{Arabic}/u', $text) ?: 0;
        $latin = preg_match_all('/[A-Za-zÀ-ÖØ-öø-ÿ]/u', $text) ?: 0;

        if ($arabic > 30 && $latin < 30) {
            return 'arabic';
        }
        if ($latin > 30 && $arabic < 30) {
            return 'latin';
        }
        if ($latin === 0 && $arabic === 0) {
            return 'unknown';
        }

        return 'mixed';
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

        throw new \RuntimeException('Impossible de lire la réponse JSON de l’IA.');
    }
}
