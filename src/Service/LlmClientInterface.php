<?php

namespace App\Service;

interface LlmClientInterface
{
    /**
     * Returns the model's raw text output (expected to be JSON string for our CV flow).
     */
    public function generateText(string $prompt): string;
}

