<?php

namespace App\AI\Providers;

use App\AI\Contracts\LlmClient;
use App\AI\DTO\ChatRequest;
use App\AI\DTO\ChatResponse;
use App\AI\Exceptions\ProviderError;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class OpenAIClient implements LlmClient
{
    private const BASE_URL = 'https://api.openai.com/v1';

    public function generateChatCompletion(ChatRequest $request): ChatResponse
    {
        $key = Config::get('ai.providers.openai.key');
        $start = (int) round(microtime(true) * 1000);

        $body = [
            'model' => $request->model,
            'messages' => $request->messages,
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxOutputTokens,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])
            ->timeout(Config::get('ai.timeout', 30))
            ->post(self::BASE_URL . '/chat/completions', $body);

        $latencyMs = (int) round(microtime(true) * 1000) - $start;
        $raw = $response->json();

        if (! $response->successful()) {
            $message = $raw['error']['message'] ?? $response->body();
            throw ProviderError::requestFailed('openai', $message);
        }

        $text = $raw['choices'][0]['message']['content'] ?? '';
        $usage = $raw['usage'] ?? null;
        $inputTokens = $usage['prompt_tokens'] ?? null;
        $outputTokens = $usage['completion_tokens'] ?? null;
        $totalTokens = $usage['total_tokens'] ?? null;

        return ChatResponse::make(
            $text,
            $raw,
            $inputTokens,
            $outputTokens,
            $totalTokens,
            $latencyMs
        );
    }
}
