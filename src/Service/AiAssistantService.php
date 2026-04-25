<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Reclamation;

class AiAssistantService
{
    private $client;
    private $apiKey;
    private $model;

    public function __construct(HttpClientInterface $client, string $geminiApiKey, string $geminiModel)
    {
        $this->client = $client;
        $this->apiKey = $geminiApiKey;
        $this->model = $geminiModel;
    }

    public function generateAiResponse(string $typeReclamationValue, ?string $contextSolution = null): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key=" . $this->apiKey;

        $details = match ($typeReclamationValue) {
            'PLATEFORME' => "Problème technique plateforme",
            'ENTREPRISE' => "Problème entreprise partenaire",
            'STAGE' => "Problème stage",
            'OFFRE_EMPLOIE' => "Problème offre d'emploi",
            default => "Réclamation générale",
        };

        $prompt = "Tu es l'assistant du projet Khadamni. Type de réclamation : $typeReclamationValue. $details. ";
        if ($contextSolution) {
            $prompt .= "Une ancienne solution existe : $contextSolution. Inspire-toi de cette solution.";
        }
        $prompt .= " Réponds poliment au client.";

        try {
            $response = $this->client->request('POST', $url, [
                'json' => [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]
            ]);

            $data = $response->toArray();
            return $data['candidates'][0]['content']['parts'][0]['text'];
        } catch (\Exception $e) {
            return "Nous avons bien reçu votre réclamation concernant : $typeReclamationValue. Un agent reviendra vers vous rapidement.";
        }
    }

    public function processNewReclamation(Reclamation $reclamation): bool
    {
        $texte = strtolower($reclamation->getDescription());
        $motsClesSimples = ['mot de passe', 'password', 'connexion', 'cv', 'modifier'];

        foreach ($motsClesSimples as $mot) {
            if (str_contains($texte, $mot)) return true;
        }

        return false;
    }
}