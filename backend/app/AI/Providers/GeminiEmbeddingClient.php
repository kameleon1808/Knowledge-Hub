<?php

namespace App\AI\Providers;

use App\AI\Contracts\EmbeddingClient;
use App\AI\DTO\EmbeddingResult;
use App\AI\Exceptions\ProviderError;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class GeminiEmbeddingClient implements EmbeddingClient
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    public function embed(array $texts): EmbeddingResult
    {
        if ($texts === []) {
            return EmbeddingResult::make([], 0, 0);
        }

        $key = Config::get('ai.providers.gemini.key');
        $model = Config::get('ai.providers.gemini.embedding_model', 'gemini-embedding-001');
        $targetDim = (int) Config::get('ai.embedding_dimension', 1536);
        $start = (int) round(microtime(true) * 1000);

        $vectors = [];
        foreach ($texts as $text) {
            $body = [
                'model' => 'models/' . $model,
                'content' => [
                    'parts' => [['text' => $text]],
                ],
                'output_dimensionality' => $targetDim,
            ];
            $response = Http::withHeaders([
                'x-goog-api-key' => $key,
                'Content-Type' => 'application/json',
            ])
                ->timeout(Config::get('ai.timeout', 30))
                ->post(self::BASE_URL . '/models/' . $model . ':embedContent', $body);

            $raw = $response->json();
            if (! $response->successful()) {
                $message = $raw['error']['message'] ?? $response->body();
                throw ProviderError::requestFailed('gemini', $message);
            }

            $values = $raw['embedding']['values'] ?? [];
            $vec = array_map('floatval', $values);
            if (count($vec) < $targetDim) {
                $vec = array_pad($vec, $targetDim, 0.0);
            } elseif (count($vec) > $targetDim) {
                $vec = array_slice($vec, 0, $targetDim);
            }
            $vectors[] = $vec;
        }

        $latencyMs = (int) round(microtime(true) * 1000) - $start;

        return EmbeddingResult::make($vectors, null, $latencyMs);
    }
}
