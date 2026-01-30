<?php

namespace App\AI\Providers;

use App\AI\Contracts\LlmClient;
use App\AI\DTO\ChatRequest;
use App\AI\DTO\ChatResponse;

/**
 * Mock LLM provider for local testing and demos. No API key or network required.
 * Returns a fixed draft answer so the AI answer flow and audit logging can be tested.
 */
class MockClient implements LlmClient
{
    public function generateChatCompletion(ChatRequest $request): ChatResponse
    {
        $start = (int) round(microtime(true) * 1000);

        $text = "**Draft answer (mock provider)**\n\n"
            . "This is a placeholder response from the mock AI provider. Use it to test the flow and audit log without an API key.\n\n"
            . "- To use real AI, set `AI_PROVIDER=openai` (or anthropic/gemini) and add the corresponding API key in `.env`.\n"
            . "- OpenAI offers a small free tier for new accounts at platform.openai.com.";

        $latencyMs = (int) round(microtime(true) * 1000) - $start;
        $raw = [
            'mock' => true,
            'model' => $request->model,
            'content' => $text,
        ];

        return ChatResponse::make(
            $text,
            $raw,
            0,
            50,
            50,
            $latencyMs
        );
    }
}
