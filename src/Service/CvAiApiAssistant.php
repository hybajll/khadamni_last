<?php

namespace App\Service;

final class CvAiApiAssistant
{
    public function __construct(
        private readonly LlmClientFactory $llmFactory,
    ) {
    }

    public function improveAndAdvise(string $originalText): CvAiResult
    {
        $clean = trim($originalText);
        if ($clean === '') {
            return new CvAiResult('', '');
        }

        // If the API key is not configured, keep a strict local fallback.
        try {
            return $this->improveWithApi($clean);
        } catch (\Throwable $e) {
            return $this->improveLocally($clean);
        }
    }

    private function improveWithApi(string $cvText): CvAiResult
    {
        $prompt = $this->buildPrompt($cvText);

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

        // Ask the model to output JSON matching the schema. (We parse JSON ourselves for portability.)
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

    private function improveLocally(string $cvText): CvAiResult
    {
        // Strict fallback: no invented content, only cleaning + formatting.
        $clean = str_replace(["\r\n", "\r"], "\n", $cvText);
        $clean = preg_replace("/\n{3,}/", "\n\n", $clean) ?? $clean;

        $lines = array_values(array_filter(array_map('trim', explode("\n", $clean)), static fn (string $l): bool => $l !== ''));
        $items = array_map(static fn (string $l): string => ltrim($l, "•-* \t"), $lines);
        $items = array_values(array_filter($items, static fn (string $l): bool => $l !== ''));

        $improved = "CONTENU\n".implode("\n", array_map(static fn (string $i): string => '- '.$i, $items));

        $advice = "- Conseil : ajoutez des dates et des chiffres (durée, outils, résultats) si possible.";

        return new CvAiResult(trim($improved), $advice);
    }

    private function buildPrompt(string $cvText): string
    {
        $languageHint = $this->detectLanguageHint($cvText);

        return <<<PROMPT
You are an AI assistant specialized in CV analysis and optimization.

Your task is to improve and restructure a CV while strictly respecting the following rules:
1. You MUST NOT add any information that is not present in the original CV.
2. You MUST NOT invent experiences, skills, or achievements.
3. You MUST only reformulate, correct, and structure the existing content.
4. Improve grammar, clarity, and professional tone.
5. Organize the CV into clear sections appropriate to the CV language (e.g. Profil / Profile / الملخص, Formation / Education / التعليم, etc.).
6. Highlight the strengths of the candidate based only on the provided content.
7. Use concise and impactful language suitable for recruiters.

Additionally:
* Detect key skills and competencies from the CV.
* Identify strong points and suggest better phrasing.
* Keep the content truthful and realistic.

Language hint (for you): {$languageHint}

Output requirements:
* Return ONLY valid JSON that matches the provided JSON schema. No markdown, no code fences.
* The field "improved_cv" must contain ONLY the improved CV (no explanations).
* Put advice/suggestions into "advice", and lists into the other fields.
* Keep the SAME language(s) as the original CV. Do NOT translate. Do not switch the CV to English/French/Arabic.
* If the original CV is Arabic: output Arabic. If it's French: output French. If it's mixed: keep it mixed.
* Use section titles in the SAME language as the CV. Do not invent section content.
* Reuse the candidate’s original wording where possible (do not paraphrase into another language).
* Formatting for improved_cv (plain text):
  - Use clear section titles on their own line (e.g. "Profil", "Expérience", "Compétences", or Arabic equivalents).
  - Use short bullet points starting with "- " (dash + space).
  - Avoid long paragraphs. Convert sentences into 2–4 concise bullets per section.
  - In "Profil"/"Résumé": maximum 4 bullets.
  - Keep the layout clean and recruiter-friendly.

IMPORTANT (repeat):
- DO NOT TRANSLATE ANYTHING.
- NE TRADUISEZ PAS LE CV.
- لا تقم بالترجمة.

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
