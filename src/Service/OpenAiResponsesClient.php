<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenAiResponsesClient implements LlmClientInterface
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
            throw new \RuntimeException('OPENAI_API_KEY est vide. Ajoutez votre clé API dans .env.local.');
        }

        // We request JSON as plain text and parse ourselves (robust across providers).
        $payload = [
            'input' => $prompt,
            'temperature' => 0.2,
            'max_output_tokens' => 1400,
        ];

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/responses', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => array_merge(['model' => $this->model], $payload),
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

        if (is_array($data) && isset($data['output_text']) && is_string($data['output_text'])) {
            return $data['output_text'];
        }

        // Fallback for older shapes: walk output->content->text
        if (is_array($data) && isset($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $out) {
                if (!is_array($out) || !isset($out['content']) || !is_array($out['content'])) {
                    continue;
                }
                foreach ($out['content'] as $content) {
                    if (is_array($content) && isset($content['type'], $content['text']) && $content['type'] === 'output_text' && is_string($content['text'])) {
                        return $content['text'];
                    }
                    if (is_array($content) && isset($content['text']) && is_string($content['text'])) {
                        return $content['text'];
                    }
                }
            }
        }

        throw new \RuntimeException('Réponse IA vide.');
    }
}
