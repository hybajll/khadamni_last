<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenRouterChatClient implements LlmClientInterface
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
            throw new \RuntimeException('OPENROUTER_API_KEY est vide. Ajoutez votre clé dans .env.local.');
        }

        $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant. Output only valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
            'timeout' => 40,
        ]);

        try {
            $status = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Connexion à l’API IA impossible. Vérifiez votre connexion internet.', 0, $e);
        }

        if ($status < 200 || $status >= 300) {
            $message = 'Erreur API IA.';
            if (is_array($data) && isset($data['error']['message']) && is_string($data['error']['message'])) {
                $message .= ' '.$data['error']['message'];
            }
            throw new \RuntimeException($message);
        }

        if (is_array($data) && isset($data['choices'][0]['message']['content']) && is_string($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        throw new \RuntimeException('Réponse IA vide.');
    }
}

