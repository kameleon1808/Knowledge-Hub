<?php

namespace App\Http\Controllers;

use App\AI\DTO\ChatRequest;
use App\AI\LlmManager;
use App\Http\Requests\AskRagRequest;
use App\Models\Project;
use App\Models\RagQuery;
use App\Services\RagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;

class ProjectRagController extends Controller
{
    public function __construct(
        private readonly RagService $rag,
        private readonly LlmManager $llm
    ) {
    }

    public function ask(AskRagRequest $request, Project $project): JsonResponse
    {
        if (! Config::get('ai.enabled')) {
            return response()->json(['message' => 'AI features are disabled.'], 422);
        }

        if (! $this->llm->isConfigured()) {
            return response()->json(['message' => 'AI is not configured. Set AI_ENABLED and the provider API key.'], 503);
        }

        $embeddingConfigured = app(\App\Services\EmbeddingService::class)->isConfigured();
        if (! $embeddingConfigured) {
            return response()->json(['message' => 'Embeddings are not configured. Set AI_PROVIDER to mock, openai, or gemini and ensure the provider API key is set (e.g. GEMINI_API_KEY for gemini).'], 503);
        }

        $questionText = $request->string('question_text')->toString();

        $ragQuery = RagQuery::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'question_text' => $questionText,
            'answer_text' => null,
            'cited_chunk_ids' => null,
            'provider' => null,
            'model' => null,
        ]);

        $auditContext = [
            'user_id' => $request->user()->id,
            'subject_type' => RagQuery::class,
            'subject_id' => $ragQuery->id,
        ];

        try {
            $chunks = $this->rag->retrieve($project, $questionText, $auditContext);
            $contextPrompt = $this->rag->buildContextPrompt($chunks);

            $systemMessage = 'Answer based ONLY on the provided context from the knowledge base. '
                . 'If the context does not contain enough information to answer, say so clearly. '
                . 'When you use information from the context, refer to the source by its number in brackets (e.g. [1], [2]).';

            $userContent = "Context:\n\n" . $contextPrompt . "\n\n---\n\nQuestion: " . $questionText;

            $model = $this->llm->defaultModel();
            $temperature = (float) Config::get('ai.temperature', 0.3);
            $maxTokens = (int) Config::get('ai.max_output_tokens', 700);

            $chatRequest = ChatRequest::make(
                $model,
                [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $userContent],
                ],
                $temperature,
                $maxTokens,
                [
                    'rag_query_id' => $ragQuery->id,
                    'project_id' => $project->id,
                ]
            );

            $result = $this->llm->generateChatCompletion($chatRequest, $auditContext);
            $answerText = trim($result->response->text);
            $citedIds = array_column($chunks, 'id');

            $ragQuery->update([
                'answer_text' => $answerText,
                'cited_chunk_ids' => $citedIds,
                'provider' => $this->llm->providerName(),
                'model' => $model,
            ]);

            app(\App\Services\ActivityLogger::class)->log(
                \App\Services\ActivityLogger::ACTION_RAG_ASKED,
                $project,
                $request->user(),
                RagQuery::class,
                $ragQuery->id,
                null
            );

            $citations = array_map(fn ($c) => [
                'id' => $c['id'],
                'excerpt' => mb_substr($c['content_text'], 0, 300) . (mb_strlen($c['content_text']) > 300 ? '…' : ''),
                'source_title' => $c['item_title'],
            ], $chunks);

            return response()->json([
                'rag_query_id' => $ragQuery->id,
                'answer_text' => $answerText,
                'citations' => $citations,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }
}
