<?php

namespace App\AI\DTO;

final class ChatResponse
{
    public function __construct(
        public readonly string $text,
        public readonly mixed $raw,
        public readonly ?int $inputTokens,
        public readonly ?int $outputTokens,
        public readonly ?int $totalTokens,
        public readonly ?int $latencyMs,
    ) {
    }

    public static function make(
        string $text,
        mixed $raw,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
        ?int $totalTokens = null,
        ?int $latencyMs = null,
    ): self {
        return new self($text, $raw, $inputTokens, $outputTokens, $totalTokens, $latencyMs);
    }
}
