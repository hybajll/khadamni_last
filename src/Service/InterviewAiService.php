<?php

namespace App\Service;

use App\Entity\Offer;

final class InterviewAiService
{
    public function __construct(private readonly LlmClientFactory $llmFactory)
    {
    }

    /**
     * @return array{offerContext: array<string, mixed>, questions: array<int, array{id:string,type:string,text:string}>}
     */
    public function generateQuestions(?Offer $offer): array
    {
        $offerContext = $this->offerToContext($offer);

        $prompt = $this->jsonOnlyPreamble().
            "Tu es un recruteur technique francophone.\n".
            "Tu dois générer 12 questions d'entretien en français, adaptées à l'offre et organisées de manière PRO: début général puis technique.\n\n".
            "OFFRE (JSON):\n".json_encode($offerContext, JSON_UNESCAPED_UNICODE)."\n\n".
            "Exigences (ordre IMPORTANT):\n".
            "- Q1-Q3: générales (présentation orientée poste, motivation, compréhension de l'offre).\n".
            "- Q4-Q11: techniques (au moins 5 questions) très liées au poste (technos, stack, domaine). Progressif (facile → dur) + au moins 2 questions niveau senior (architecture/scalabilité/choix).\n".
            "- Q12: clôture (questions pertinentes à poser).\n".
            "Règles:\n".
            "- Varier les questions (pas répétitives).\n".
            "- Toujours ancrer dans l'offre quand c'est possible (mots-clés de la description).\n".
            "- Éviter les questions trop génériques côté technique: être concret.\n\n".
            "Réponds en JSON strict au format:\n".
            "{ \"questions\": [ {\"id\":\"q1\",\"type\":\"technical\",\"text\":\"...\"}, ... ] }\n";

        $text = $this->llmFactory->client()->generateText($prompt);
        $out = $this->decodeJson($text);

        $questions = $out['questions'] ?? [];
        if (!is_array($questions) || count($questions) < 4) {
            $questions = [
                ['id' => 'q1', 'type' => 'rh', 'text' => 'Présentez-vous en 60 secondes.'],
                ['id' => 'q2', 'type' => 'technical', 'text' => 'Décrivez une réalisation technique récente et son impact (chiffré).'],
                ['id' => 'q3', 'type' => 'behavioral', 'text' => "Donnez un exemple STAR d'un problème difficile que vous avez résolu."],
                ['id' => 'q4', 'type' => 'closing', 'text' => 'Quelles questions poseriez-vous au recruteur ?'],
            ];
        } else {
            $normalized = [];
            foreach ($questions as $idx => $q) {
                if (!is_array($q)) {
                    continue;
                }
                $textQ = isset($q['text']) && is_string($q['text']) ? trim($q['text']) : '';
                if ($textQ === '') {
                    continue;
                }
                $id = isset($q['id']) && is_string($q['id']) ? trim($q['id']) : ('q'.($idx + 1));
                $type = isset($q['type']) && is_string($q['type']) ? trim($q['type']) : 'technical';
                $normalized[] = ['id' => $id, 'type' => $type, 'text' => $textQ];
            }
            if ($normalized) {
                $questions = $this->enforceOrder($normalized, $offerContext);
            }
        }

        return [
            'offerContext' => $offerContext,
            'questions' => $questions,
        ];
    }

    /**
     * @param array<string, mixed> $offerContext
     * @param array{id:string,type:string,text:string} $question
     * @return array{score:int,strengths:array<int,string>,weaknesses:array<int,string>,tips:array<int,string>,perfectAnswer:string}
     */
    public function evaluate(array $offerContext, array $question, string $userAnswer): array
    {
        $prompt = $this->jsonOnlyPreamble().
            "Tu es un recruteur technique francophone très exigeant.\n".
            "Évalue la réponse de l'utilisateur et propose une \"réponse parfaite\" courte, professionnelle et adaptée à l'offre + la question.\n\n".
            "OFFRE (JSON):\n".json_encode($offerContext, JSON_UNESCAPED_UNICODE)."\n\n".
            "QUESTION (JSON):\n".json_encode($question, JSON_UNESCAPED_UNICODE)."\n\n".
            "RÉPONSE UTILISATEUR:\n".$userAnswer."\n\n".
            "Réponds en JSON strict au format:\n".
            "{\n".
            "  \"score\": 0-100,\n".
            "  \"strengths\": [\"...\"],\n".
            "  \"weaknesses\": [\"...\"],\n".
            "  \"tips\": [\"...\"],\n".
            "  \"perfectAnswer\": \"...\"\n".
            "}\n\n".
            "Contraintes:\n".
            "- Si la réponse est trop courte ou hors sujet => score bas.\n".
            "- perfectAnswer: 6–12 lignes max, très concret, avec 1–2 chiffres si possible.\n".
            "- tips: actions concrètes.\n";

        $text = $this->llmFactory->client()->generateText($prompt);
        $out = $this->decodeJson($text);

        $score = (int) ($out['score'] ?? 0);
        $score = max(0, min(100, $score));

        $strengths = $this->stringsArray($out['strengths'] ?? []);
        $weaknesses = $this->stringsArray($out['weaknesses'] ?? []);
        $tips = $this->stringsArray($out['tips'] ?? []);

        $perfect = is_string($out['perfectAnswer'] ?? null) ? trim((string) $out['perfectAnswer']) : '';
        if ($perfect === '') {
            $perfect = "Réponse parfaite (exemple) :\n- Contexte : ...\n- Actions : ...\n- Résultat : ...\n- Lien avec le poste : ...";
        }

        return [
            'score' => $score,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'tips' => $tips,
            'perfectAnswer' => $perfect,
        ];
    }

    public function perfectAnswer(array $offerContext, array $question): string
    {
        $questionText = is_string($question['text'] ?? null) ? (string) $question['text'] : '';
        $prompt = $this->jsonOnlyPreamble().
            "Tu es un recruteur technique francophone.\n".
            "Génère une \"réponse parfaite\" SPECIFIQUE à la question, adaptée à l'offre.\n\n".
            "OFFRE (JSON):\n".json_encode($offerContext, JSON_UNESCAPED_UNICODE)."\n\n".
            "QUESTION (JSON):\n".json_encode($question, JSON_UNESCAPED_UNICODE)."\n\n".
            "Contraintes:\n".
            "- Donne une réponse prête à être prononcée par un candidat.\n".
            "- 10 à 16 lignes, ton professionnel, clair, structuré.\n".
            "- Inclure 1 exemple concret + 1 résultat chiffré (même si c'est un exemple plausible).\n".
            "- Utiliser des mots-clés de l'offre si possible (stack/domaine).\n\n".
            "Réponds en JSON strict au format:\n".
            "{ \"perfectAnswer\": \"...\" }\n";

        $prompt .=
            "\nContraintes additionnelles:\n".
            "- IMPORTANT: la réponse doit reprendre au moins 2 mots-clés de la question (ex: cache, DB, monitoring, trade-off, architecture, scalabilité).\n".
            ($questionText !== '' ? "- La réponse doit répondre exactement à : \"$questionText\".\n" : '').
            "- Ne pas donner une réponse générique réutilisable pour une autre question.\n".
            "- Interdit d'utiliser des placeholders du type [X], [projet], [techno]. Invente un exemple plausible concret avec 1 résultat chiffré.\n".
            "- La réponse doit être spécifique (nomme une techno/outil/approche) et coller au niveau de la question.\n";

        $text = $this->llmFactory->client()->generateText($prompt);
        $out = $this->decodeJson($text);
        $perfect = is_string($out['perfectAnswer'] ?? null) ? trim((string) $out['perfectAnswer']) : '';
        if ($perfect === '') {
            throw new \RuntimeException('Réponse parfaite vide.');
        }
        return $perfect;
    }

    private function jsonOnlyPreamble(): string
    {
        return "IMPORTANT: retourne UNIQUEMENT du JSON valide. Pas de markdown. Pas de texte avant/après.\n\n";
    }

    /**
     * @return array<string, mixed>
     */
    private function offerToContext(?Offer $offer): array
    {
        if (!$offer) {
            return [
                'title' => null,
                'domain' => null,
                'location' => null,
                'contractType' => null,
                'experienceLevel' => null,
                'description' => null,
            ];
        }

        return [
            'title' => $offer->getTitle(),
            'domain' => $offer->getDomain(),
            'location' => $offer->getLocation(),
            'contractType' => $offer->getContractType(),
            'experienceLevel' => $offer->getExperienceLevel(),
            'description' => $offer->getDescription(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $text): array
    {
        $trim = trim($text);
        $trim = preg_replace('/^```(?:json)?/i', '', $trim) ?? $trim;
        $trim = preg_replace('/```$/', '', $trim) ?? $trim;
        $trim = trim($trim);

        $decoded = json_decode($trim, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $trim, $m)) {
            $decoded2 = json_decode($m[0], true);
            if (is_array($decoded2)) {
                return $decoded2;
            }
        }

        throw new \RuntimeException('Réponse IA invalide (JSON).');
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function stringsArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $s = trim($item);
                if ($s !== '') {
                    $out[] = $s;
                }
            }
        }
        return array_values(array_slice($out, 0, 6));
    }

    /**
     * Ensure the first item is always an agent intro, then the first real question is "présentez-vous".
     *
     * @param array<int, array{id:string,type:string,text:string}> $questions
     * @param array<string, mixed> $offerContext
     * @return array<int, array{id:string,type:string,text:string}>
     */
    private function enforceOrder(array $questions, array $offerContext): array
    {
        $title = is_string($offerContext['title'] ?? null) ? trim((string) $offerContext['title']) : '';
        $location = is_string($offerContext['location'] ?? null) ? trim((string) $offerContext['location']) : '';
        $domain = is_string($offerContext['domain'] ?? null) ? trim((string) $offerContext['domain']) : '';

        $offerLabel = $title !== '' ? $title : 'ce poste';
        if ($domain !== '') {
            $offerLabel .= " ($domain)";
        }
        if ($location !== '') {
            $offerLabel .= " — $location";
        }

        // Find an existing "présentez-vous" question.
        $presentIndex = null;
        foreach ($questions as $i => $q) {
            $t = mb_strtolower($q['text']);
            if (str_contains($t, 'présente') || str_contains($t, 'présenter') || str_contains($t, 'présentation') || str_contains($t, 'presentation')) {
                $presentIndex = $i;
                break;
            }
        }

        $out = [];
        $out[] = [
            'id' => 'intro_agent',
            'type' => 'intro',
            'text' => "Bonjour, je suis votre recruteur. Je vais d’abord vous poser quelques questions générales, puis nous irons vers des questions techniques liées à l’offre. Cliquez sur « Question suivante » pour commencer.",
        ];

        $present = [
            'id' => 'presenter',
            'type' => 'general',
            'text' => "Présentez-vous en 60 secondes pour le poste : $offerLabel.",
        ];
        $out[] = $presentIndex !== null ? $questions[$presentIndex] : $present;
        if ($presentIndex !== null) {
            unset($questions[$presentIndex]);
        }

        // Keep the rest in model order, and aim for 13 items total:
        // 1 intro agent + 12 questions.
        foreach (array_values($questions) as $q) {
            $out[] = $q;
            if (count($out) >= 13) {
                break;
            }
        }

        // If model returned fewer, pad with a closing question.
        if (count($out) < 13) {
            $out[] = [
                'id' => 'closing_extra',
                'type' => 'closing',
                'text' => "Quelles questions pertinentes poseriez-vous au recruteur pour \"$offerLabel\" ?",
            ];
        }

        return array_values($out);
    }
}
