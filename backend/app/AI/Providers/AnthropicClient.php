<?php

namespace App\AI\Providers;

use App\AI\Contracts\LlmClient;
use App\AI\DTO\ChatRequest;
use App\AI\DTO\ChatResponse;
use App\AI\Exceptions\ProviderError;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class AnthropicClient implements LlmClient
{
    private const BASE_URL = 'https://api.anthropic.com/v1';

    public function generateChatCompletion(ChatRequest $request): ChatResponse
    {
        $key = Config::get('ai.providers.anthropic.key');
        $start = (int) round(microtime(true) * 1000);

        $messages = $this->toAnthropicMessages($request->messages);
        $system = $this->extractSystemMessage($request->messages);

        $body = [
            'model' => $request->model,
            'max_tokens' => $request->maxOutputTokens,
            'messages' => $messages,
        ];
        if ($system !== null) {
            $body['system'] = $system;
        }

        $response = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])
            ->timeout(Config::get('ai.timeout', 30))
            ->post(self::BASE_URL . '/messages', $body);

        $latencyMs = (int) round(microtime(true) * 1000) - $start;
        $raw = $response->json();

        if (! $response->successful()) {
            $message = $raw['error']['message'] ?? $response->body();
            throw ProviderError::requestFailed('anthropic', $message);
        }

        $text = '';
        foreach ($raw['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'] ?? '';
            }
        }
        $usage = $raw['usage'] ?? null;
        $inputTokens = $usage['input_tokens'] ?? null;
        $outputTokens = $usage['output_tokens'] ?? null;
        $totalTokens = ($inputTokens !== null && $outputTokens !== null) ? $inputTokens + $outputTokens : null;

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
     * @return array<int, array{role: string, content: string}>
     */
    private function toAnthropicMessages(array $messages): array
    {
        $out = [];
        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            if (strtolower($role) === 'system') {
                continue;
            }
            $out[] = [
                'role' => $role === 'assistant' ? 'assistant' : 'user',
                'content' => $m['content'] ?? '',
            ];
        }
        return $out;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function extractSystemMessage(array $messages): ?string
    {
        $first = $messages[0] ?? null;
        if ($first !== null && strtolower($first['role'] ?? '') === 'system') {
            return $first['content'] ?? '';
        }
        return null;
    }
}
