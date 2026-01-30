<?php

namespace App\AI;

use App\AI\Audit\AiAuditLogger;
use App\AI\Contracts\EmbeddingClient;
use App\AI\DTO\EmbeddingResult;
use App\AI\Exceptions\NotConfigured;
use App\AI\Providers\GeminiEmbeddingClient;
use App\AI\Providers\MockEmbeddingClient;
use App\AI\Providers\OpenAIEmbeddingClient;
use Illuminate\Support\Facades\Config;

class EmbeddingManager
{
    private const PROVIDER_MAP = [
        'mock' => MockEmbeddingClient::class,
        'openai' => OpenAIEmbeddingClient::class,
        'gemini' => GeminiEmbeddingClient::class,
    ];

    public function __construct(
        private readonly AiAuditLogger $auditLogger
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) Config::get('ai.enabled');
    }

    private static function currentProvider(): string
    {
        $provider = env('AI_PROVIDER');
        if ($provider !== null && $provider !== '') {
            return $provider;
        }

        return (string) Config::get('ai.provider', 'openai');
    }

    public function isConfigured(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $provider = self::currentProvider();
        if (! isset(self::PROVIDER_MAP[$provider])) {
            return false;
        }

        if ($provider === 'mock') {
            return true;
        }

        $key = Config::get("ai.providers.{$provider}.key");

        return $key !== null && $key !== '';
    }

    /**
     * @throws NotConfigured
     */
    public function client(): EmbeddingClient
    {
        if (! $this->isEnabled()) {
            throw NotConfigured::aiDisabled();
        }

        $provider = self::currentProvider();
        if (! isset(self::PROVIDER_MAP[$provider])) {
            throw new NotConfigured(
                "Embeddings not supported for provider: {$provider}. Use mock, openai, or gemini for RAG embeddings."
            );
        }

        if ($provider !== 'mock') {
            $key = Config::get("ai.providers.{$provider}.key");
            if ($key === null || $key === '') {
                throw NotConfigured::missingApiKey($provider);
            }
        }

        return app(self::PROVIDER_MAP[$provider]);
    }

    public function providerName(): string
    {
        return self::currentProvider();
    }

    public function embeddingModel(): string
    {
        $provider = self::currentProvider();
        $model = Config::get("ai.providers.{$provider}.embedding_model");

        return $model !== null && $model !== '' ? $model : 'text-embedding-3-small';
    }

    /**
     * Embed texts and create an audit log entry (success or error).
     *
     * @param  array<int, string>  $texts
     * @param  array{user_id?: int|null, subject_type: string, subject_id: int}  $auditContext
     */
    public function embedWithAudit(array $texts, array $auditContext): EmbeddingResult
    {
        $provider = $this->providerName();
        $model = $this->embeddingModel();

        $requestPayload = [
            'model' => $model,
            'input_count' => count($texts),
            'input_preview' => isset($texts[0]) ? mb_substr($texts[0], 0, 200) : null,
        ];

        try {
            $result = $this->client()->embed($texts);
            $this->auditLogger->logEmbeddingSuccess($provider, $model, $requestPayload, $result, $auditContext);

            return $result;
        } catch (NotConfigured $e) {
            $this->auditLogger->logEmbeddingError($provider, $model, $requestPayload, $e, $auditContext);
            throw $e;
        } catch (\Throwable $e) {
            $this->auditLogger->logEmbeddingError($provider, $model, $requestPayload, $e, $auditContext);
            throw $e;
        }
    }
}
