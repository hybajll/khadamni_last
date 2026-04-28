<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GeminiGenerateContentClient implements LlmClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $model,
    ) {
    }

    public function generateText(string $prompt): string
    {
        if (trim($this->apiKey) === '') {
            throw new \RuntimeException('GEMINI_API_KEY est vide. Ajoutez votre clé dans .env.local.');
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            rawurlencode($this->model)
        );

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'x-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ],
            'timeout' => 40,
        ]);

        try {
            $status = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Connexion à Gemini impossible. Vérifiez votre connexion internet.', 0, $e);
        }

        if ($status < 200 || $status >= 300) {
            $message = 'Erreur API IA (Gemini).';
            if (is_array($data) && isset($data['error']['message']) && is_string($data['error']['message'])) {
                $message .= ' '.$data['error']['message'];
            }
            throw new \RuntimeException($message);
        }

        if (
            is_array($data)
            && isset($data['candidates'][0]['content']['parts'][0]['text'])
            && is_string($data['candidates'][0]['content']['parts'][0]['text'])
        ) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        throw new \RuntimeException('Réponse IA vide.');
    }
}

