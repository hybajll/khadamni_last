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

    /**
     * Translate only (no rewriting beyond necessary cleanup).
     *
     * @param string $targetLang fr|en
     */
    public function translateOnly(string $originalText, string $targetLang): CvAiResult
    {
        $clean = trim($originalText);
        if ($clean === '') {
            return new CvAiResult('', '');
        }

        $targetLang = strtolower($targetLang);
        if (!in_array($targetLang, ['fr', 'en'], true)) {
            throw new \InvalidArgumentException('Langue cible invalide.');
        }

        try {
            return $this->translateWithApi($clean, $targetLang);
        } catch (\Throwable $e) {
            // Without the API, we cannot translate reliably. Show a useful message for debugging.
            $msg = trim($e->getMessage());
            $msg = $this->simplifyProviderError($msg);
            if ($msg === '') {
                $msg = 'Vérifiez votre clé API et votre connexion internet.';
            }

            return new CvAiResult('', 'Traduction indisponible : '.$msg);
        }
    }

    private function simplifyProviderError(string $message): string
    {
        $msg = trim($message);
        if ($msg === '') {
            return '';
        }

        // Gemini free-tier rate limit / quota message: "Please retry in Xs."
        if (stripos($msg, 'Erreur API IA (Gemini)') !== false || stripos($msg, 'gemini') !== false) {
            if (preg_match('/retry in\\s+([0-9]+(?:\\.[0-9]+)?)s/i', $msg, $m)) {
                $seconds = (float) $m[1];
                $secondsRounded = (int) max(1, ceil($seconds));
                return 'Quota/limite atteinte. Réessayez dans '.$secondsRounded.' seconde(s).';
            }
            if (stripos($msg, 'quota') !== false || stripos($msg, 'rate') !== false || stripos($msg, 'limit') !== false) {
                return 'Quota/limite atteinte. Réessayez dans quelques secondes (ou attendez 1 minute).';
            }
        }

        return $msg;
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

    private function translateWithApi(string $cvText, string $targetLang): CvAiResult
    {
        $prompt = $this->buildTranslatePrompt($cvText, $targetLang);

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'translated_cv' => ['type' => 'string'],
            ],
            'required' => ['translated_cv'],
        ];

        $rawText = $this->llmFactory->client()->generateText(
            $prompt."\n\nJSON Schema:\n".json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // Some models may still answer with plain text even when asked for JSON.
        // We accept either JSON (preferred) or raw text (fallback).
        $translated = '';
        try {
            $decoded = $this->decodeJson($rawText);
            $translated = trim((string) ($decoded['translated_cv'] ?? ''));
        } catch (\Throwable) {
            $translated = trim($rawText);
        }

        return new CvAiResult($translated, '');
    }

    private function buildPrompt(string $cvText, ?string $targetLang): string
    {
        $languageHint = $this->detectLanguageHint($cvText);
        $target = $targetLang ? strtoupper($targetLang) : 'SAME_AS_ORIGINAL';

        $translationRules = $targetLang === null
            ? "Keep the SAME language(s) as the original CV. Do NOT translate."
            : "Rewrite and translate the CV into the target language: {$target}. Keep it consistent (French OR English only).";

        return <<<PROMPT
You are an expert CV editor for recruiters.

GOAL
Improve the CV to be more professional, clearer, and better structured, while keeping the SAME style as the original CV (same kind of headings, same tone, same ordering as much as possible).

STRICT RULES (MUST FOLLOW)
1) Do NOT add any information that is not present in the original CV.
2) Do NOT invent experiences, education, dates, companies, skills, levels, or certifications.
3) You may ONLY: fix language, reorganize, deduplicate, and clarify what already exists.
4) Preserve meaning. If a detail is ambiguous, keep it generic rather than guessing.

SMART ORGANIZATION (BE MORE \"SMART\")
- Detect and group information into logical sections (Contact, Profile, Education, Experience, Projects, Skills, Languages, Certifications, Interests) BUT:
  * Keep the SAME section titles if the CV already has them.
  * Keep the SAME order of sections when possible.
  * Only create a missing section if the information clearly exists in the CV text.
- Convert messy paragraphs into clean bullet points.
- Remove repetitions and filler phrases.
- Normalize formatting consistently (dates style, punctuation, capitalization) without changing facts.
- Make bullet points action-oriented and recruiter-friendly (clear contribution), but never fabricate results.

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
  - Do NOT use emojis or decorative symbols.
  - Keep the same look as the original CV:
    * If the CV uses ALL CAPS headings, keep ALL CAPS headings.
    * If the CV uses ":" after headings, keep that convention.
    * If the CV uses bullets, use bullets. Prefer "- " unless the CV clearly uses another bullet prefix consistently.
  - Avoid long paragraphs (max 2 lines); prefer bullets.
  - Profile/Summary: maximum 4 bullets.
  - Keep layout clean and recruiter-friendly.
  - Keep it 1-page friendly (short bullets, no repetitions).

CV CONTENT:
{$cvText}
PROMPT;
    }

    private function buildTranslatePrompt(string $cvText, string $targetLang): string
    {
        $target = strtoupper($targetLang);

        return <<<PROMPT
You are an expert CV translator.

Task:
- Translate the CV to the target language: {$target}.
- DO NOT add or invent any information.
- Keep names, emails, phone numbers, dates, and technologies as-is.
- Keep the structure similar (sections and bullets) but translate headings and sentences.
- Output must be clean and recruiter-friendly.

Output requirements:
- Return ONLY valid JSON matching the schema (no markdown, no code fences).
- Use exactly this key: "translated_cv".
- The field "translated_cv" must contain ONLY the translated CV (plain text).
- Do NOT include explanations.

Example output:
{"translated_cv":"..."}

CV:
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
