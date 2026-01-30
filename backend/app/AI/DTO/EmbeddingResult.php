<?php

namespace App\AI\DTO;

final class EmbeddingResult
{
    /**
     * @param  array<int, array<int, float>>  $vectors
     */
    public function __construct(
        public readonly array $vectors,
        public readonly ?int $totalTokens,
        public readonly ?int $latencyMs,
    ) {
    }

    /**
     * @param  array<int, array<int, float>>  $vectors
     */
    public static function make(array $vectors, ?int $totalTokens = null, ?int $latencyMs = null): self
    {
        return new self($vectors, $totalTokens, $latencyMs);
    }
}
