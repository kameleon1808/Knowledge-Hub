<?php

namespace App\AI\Contracts;

use App\AI\DTO\ChatRequest;
use App\AI\DTO\ChatResponse;

interface LlmClient
{
    public function generateChatCompletion(ChatRequest $request): ChatResponse;
}
