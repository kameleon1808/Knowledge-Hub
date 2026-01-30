<?php

namespace App\AI\Providers;

use App\AI\Contracts\EmbeddingClient;
use App\AI\DTO\EmbeddingResult;
use App\AI\Exceptions\ProviderError;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class OpenAIEmbeddingClient implements EmbeddingClient
{
    private const BASE_URL = 'https://api.openai.com/v1';

    public function embed(array $texts): EmbeddingResult
    {
        if ($texts === []) {
            return EmbeddingResult::make([], 0, 0);
        }

        $key = Config::get('ai.providers.openai.key');
        $model = Config::get('ai.providers.openai.embedding_model', 'text-embedding-3-small');
        $start = (int) round(microtime(true) * 1000);

        $body = [
            'model' => $model,
            'input' => array_values($texts),
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])
            ->timeout(Config::get('ai.timeout', 30))
            ->post(self::BASE_URL . '/embeddings', $body);

        $latencyMs = (int) round(microtime(true) * 1000) - $start;
        $raw = $response->json();

        if (! $response->successful()) {
            $message = $raw['error']['message'] ?? $response->body();
            throw ProviderError::requestFailed('openai', $message);
        }

        $data = $raw['data'] ?? [];
        $vectors = [];
        foreach ($data as $item) {
            $emb = $item['embedding'] ?? [];
            $vectors[] = array_map('floatval', $emb);
        }

        $usage = $raw['usage'] ?? null;
        $totalTokens = isset($usage['total_tokens']) ? (int) $usage['total_tokens'] : null;

        return EmbeddingResult::make($vectors, $totalTokens, $latencyMs);
    }
}
