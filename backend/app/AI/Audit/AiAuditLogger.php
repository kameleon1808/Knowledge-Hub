<?php

namespace App\AI\Audit;

use App\AI\DTO\ChatRequest;
use App\AI\DTO\ChatResponse;
use App\AI\DTO\EmbeddingResult;
use App\Models\AiAuditLog;
use Illuminate\Support\Str;

class AiAuditLogger
{
    /**
     * Log a successful AI call. Request payload must not contain API keys.
     *
     * @param  array{user_id?: int|null, subject_type: string, subject_id: int}  $context
     */
    public function logSuccess(
        string $provider,
        string $model,
        ChatRequest $request,
        ChatResponse $response,
        array $context
    ): AiAuditLog {
        $requestPayload = $this->sanitizedRequestPayload($request);

        return AiAuditLog::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $context['user_id'] ?? null,
            'subject_type' => $context['subject_type'],
            'subject_id' => $context['subject_id'],
            'provider' => $provider,
            'model' => $model,
            'request_payload' => $requestPayload,
            'response_payload' => $response->raw,
            'response_text' => $response->text,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'total_tokens' => $response->totalTokens,
            'status' => AiAuditLog::statusSuccess(),
            'error_message' => null,
            'latency_ms' => $response->latencyMs,
        ]);
    }

    /**
     * Log a failed AI call.
     *
     * @param  array{user_id?: int|null, subject_type: string, subject_id: int}  $context
     */
    public function logError(
        string $provider,
        string $model,
        ChatRequest $request,
        \Throwable $exception,
        array $context
    ): AiAuditLog {
        $requestPayload = $this->sanitizedRequestPayload($request);

        return AiAuditLog::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $context['user_id'] ?? null,
            'subject_type' => $context['subject_type'],
            'subject_id' => $context['subject_id'],
            'provider' => $provider,
            'model' => $model,
            'request_payload' => $requestPayload,
            'response_payload' => null,
            'response_text' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'total_tokens' => null,
            'status' => AiAuditLog::statusError(),
            'error_message' => $exception->getMessage(),
            'latency_ms' => null,
        ]);
    }

    /**
     * Log a successful embedding call. No full vectors in response_payload to avoid huge storage.
     *
     * @param  array{user_id?: int|null, subject_type: string, subject_id: int}  $context
     * @param  array{model: string, input_count: int, input_preview?: string}  $requestPayload
     */
    public function logEmbeddingSuccess(
        string $provider,
        string $model,
        array $requestPayload,
        EmbeddingResult $result,
        array $context
    ): AiAuditLog {
        $responsePayload = [
            'embedding_count' => count($result->vectors),
            'dimensions' => isset($result->vectors[0]) ? count($result->vectors[0]) : 0,
            'total_tokens' => $result->totalTokens,
        ];

        return AiAuditLog::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $context['user_id'] ?? null,
            'subject_type' => $context['subject_type'],
            'subject_id' => $context['subject_id'],
            'provider' => $provider,
            'model' => $model,
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload,
            'response_text' => null,
            'input_tokens' => $result->totalTokens,
            'output_tokens' => null,
            'total_tokens' => $result->totalTokens,
            'status' => AiAuditLog::statusSuccess(),
            'error_message' => null,
            'latency_ms' => $result->latencyMs,
        ]);
    }

    /**
     * Log a failed embedding call.
     *
     * @param  array{user_id?: int|null, subject_type: string, subject_id: int}  $context
     * @param  array{model: string, input_count: int}  $requestPayload
     */
    public function logEmbeddingError(
        string $provider,
        string $model,
        array $requestPayload,
        \Throwable $exception,
        array $context
    ): AiAuditLog {
        return AiAuditLog::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $context['user_id'] ?? null,
            'subject_type' => $context['subject_type'],
            'subject_id' => $context['subject_id'],
            'provider' => $provider,
            'model' => $model,
            'request_payload' => $requestPayload,
            'response_payload' => null,
            'response_text' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'total_tokens' => null,
            'status' => AiAuditLog::statusError(),
            'error_message' => $exception->getMessage(),
            'latency_ms' => null,
        ]);
    }

    /**
     * Build request payload for storage. Must never include API keys or secrets.
     *
     * @return array<string, mixed>
     */
    private function sanitizedRequestPayload(ChatRequest $request): array
    {
        return [
            'model' => $request->model,
            'messages' => $request->messages,
            'temperature' => $request->temperature,
            'max_output_tokens' => $request->maxOutputTokens,
            'metadata' => $request->metadata,
        ];
    }
}
