<?php

namespace App\AI\DTO;

final class ChatRequest
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public readonly string $model,
        public readonly array $messages,
        public readonly float $temperature,
        public readonly int $maxOutputTokens,
        public readonly ?array $metadata = null,
    ) {
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>|null  $metadata
     */
    public static function make(
        string $model,
        array $messages,
        float $temperature,
        int $maxOutputTokens,
        ?array $metadata = null,
    ): self {
        return new self($model, $messages, $temperature, $maxOutputTokens, $metadata);
    }
}
