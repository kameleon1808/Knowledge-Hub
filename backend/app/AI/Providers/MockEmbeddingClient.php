<?php

namespace App\AI\Providers;

use App\AI\Contracts\EmbeddingClient;
use App\AI\DTO\EmbeddingResult;
use Illuminate\Support\Facades\Config;

class MockEmbeddingClient implements EmbeddingClient
{
    public function embed(array $texts): EmbeddingResult
    {
        $dim = Config::get('ai.embedding_dimension', 1536);
        $vectors = [];

        foreach ($texts as $i => $text) {
            $vec = [];
            $seed = crc32($text) + $i;
            mt_srand($seed);
            for ($j = 0; $j < $dim; $j++) {
                $vec[] = (mt_rand() / mt_getrandmax()) * 2 - 1;
            }
            $norm = sqrt(array_sum(array_map(fn ($x) => $x * $x, $vec)));
            if ($norm > 0) {
                $vec = array_map(fn ($x) => $x / $norm, $vec);
            }
            $vectors[] = $vec;
        }

        $totalTokens = (int) array_sum(array_map(fn ($t) => (int) (strlen($t) / 4), $texts));

        return EmbeddingResult::make($vectors, $totalTokens, 0);
    }
}
