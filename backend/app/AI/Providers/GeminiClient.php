<?php

namespace App\AI\Providers;

use App\AI\Contracts\LlmClient;
use App\AI\DTO\ChatRequest;
use App\AI\DTO\ChatResponse;
use App\AI\Exceptions\ProviderError;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class GeminiClient implements LlmClient
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    public function generateChatCompletion(ChatRequest $request): ChatResponse
    {
        $key = Config::get('ai.providers.gemini.key');
        $start = (int) round(microtime(true) * 1000);

        $contents = $this->toGeminiContents($request->messages);

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $request->temperature,
                'maxOutputTokens' => $request->maxOutputTokens,
            ],
        ];

        $url = self::BASE_URL . '/models/' . $request->model . ':generateContent?key=' . $key;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
            ->timeout(Config::get('ai.timeout', 30))
            ->post($url, $body);

        $latencyMs = (int) round(microtime(true) * 1000) - $start;
        $raw = $response->json();

        if (! $response->successful()) {
            $message = $raw['error']['message'] ?? $response->body();
            throw ProviderError::requestFailed('gemini', $message);
        }

        $text = '';
        $candidates = $raw['candidates'] ?? [];
        if (isset($candidates[0]['content']['parts'])) {
            foreach ($candidates[0]['content']['parts'] as $part) {
                $text .= $part['text'] ?? '';
            }
        }
        $usage = $raw['usageMetadata'] ?? null;
        $inputTokens = $usage['promptTokenCount'] ?? null;
        $outputTokens = $usage['candidatesTokenCount'] ?? null;
        $totalTokens = $usage['totalTokenCount'] ?? null;

        return ChatResponse::make(
            $text,
            $raw,
            $inputTokens,
            $outputTokens,
            $totalTokens,
            $latencyMs
        );
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<int, array{role?: string, parts: array<int, array{text: string}>}>
     */
    private function toGeminiContents(array $messages): array
    {
        $contents = [];
        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            $content = $m['content'] ?? '';
            $contents[] = [
                'role' => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $content]],
            ];
        }
        return $contents;
    }
}
