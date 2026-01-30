<?php

namespace App\AI;

use App\AI\Audit\AiAuditLogger;
use App\AI\Contracts\LlmClient;
use App\AI\DTO\ChatRequest;
use App\AI\DTO\ChatResponse;
use App\AI\DTO\GenerateChatResult;
use App\AI\Exceptions\NotConfigured;
use App\AI\Providers\AnthropicClient;
use App\AI\Providers\GeminiClient;
use App\AI\Providers\OpenAIClient;
use Illuminate\Support\Facades\Config;

class LlmManager
{
    public function __construct(
        private readonly AiAuditLogger $auditLogger
    ) {
    }
    private const PROVIDER_MAP = [
        'openai' => OpenAIClient::class,
        'anthropic' => AnthropicClient::class,
        'gemini' => GeminiClient::class,
    ];

    public function isEnabled(): bool
    {
        return (bool) Config::get('ai.enabled');
    }

    public function isConfigured(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $provider = Config::get('ai.provider', 'openai');
        if (! isset(self::PROVIDER_MAP[$provider])) {
            return false;
        }

        $key = Config::get("ai.providers.{$provider}.key");

        return $key !== null && $key !== '';
    }

    /**
     * @throws NotConfigured
     */
    public function client(): LlmClient
    {
        if (! $this->isEnabled()) {
            throw NotConfigured::aiDisabled();
        }

        $provider = Config::get('ai.provider', 'openai');
        if (! isset(self::PROVIDER_MAP[$provider])) {
            throw new NotConfigured("Unknown AI provider: {$provider}. Use openai, anthropic, or gemini.");
        }

        $key = Config::get("ai.providers.{$provider}.key");
        if ($key === null || $key === '') {
            throw NotConfigured::missingApiKey($provider);
        }

        $class = self::PROVIDER_MAP[$provider];

        return app($class);
    }

    /**
     * Generate completion and create an audit log entry (success or error).
     *
     * @param  array{user_id?: int|null, subject_type: string, subject_id: int}  $auditContext
     */
    public function generateChatCompletion(ChatRequest $request, array $auditContext): GenerateChatResult
    {
        $provider = $this->providerName();
        $model = $request->model;

        try {
            $response = $this->client()->generateChatCompletion($request);
            $auditLog = $this->auditLogger->logSuccess($provider, $model, $request, $response, $auditContext);

            return GenerateChatResult::make($response, $auditLog);
        } catch (NotConfigured $e) {
            $this->auditLogger->logError($provider, $model, $request, $e, $auditContext);
            throw $e;
        } catch (\Throwable $e) {
            $this->auditLogger->logError($provider, $model, $request, $e, $auditContext);
            throw $e;
        }
    }

    public function providerName(): string
    {
        return (string) Config::get('ai.provider', 'openai');
    }

    public function defaultModel(): string
    {
        $provider = Config::get('ai.provider', 'openai');
        $model = Config::get('ai.model');
        if ($model !== null && $model !== '') {
            return $model;
        }

        return (string) Config::get("ai.providers.{$provider}.default_model", 'gpt-4o-mini');
    }
}
