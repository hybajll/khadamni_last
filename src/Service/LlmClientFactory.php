<?php

namespace App\Service;

final class LlmClientFactory
{
    public function __construct(
        private readonly OpenAiResponsesClient $openAiClient,
        private readonly OpenRouterChatClient $openRouterClient,
        private readonly GeminiGenerateContentClient $geminiClient,
        private readonly string $provider,
    ) {
    }

    public function client(): LlmClientInterface
    {
        $provider = mb_strtolower(trim($this->provider));

        return match ($provider) {
            'gemini' => $this->geminiClient,
            'openrouter' => $this->openRouterClient,
            'openai' => $this->openAiClient,
            default => $this->openRouterClient,
        };
    }
}
