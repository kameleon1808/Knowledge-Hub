<?php

namespace App\Http\Controllers;

use App\AI\Exceptions\NotConfigured;
use App\AI\Exceptions\ProviderError;
use App\Events\NewAnswerPosted;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Question;
use App\Services\AiAnswerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class AiAnswerController extends Controller
{
    public function __construct(
        private readonly AiAnswerService $aiAnswerService
    ) {
    }

    /**
     * Generate an AI draft answer for the question (manual action).
     */
    public function store(Request $request, Question $question): JsonResponse
    {
        $this->authorize('generateAiAnswer', $question);

        if (! Config::get('ai.enabled')) {
            return response()->json([
                'message' => 'AI features are disabled. Enable AI in configuration to generate answers.',
            ], 422);
        }

        try {
            $answer = $this->aiAnswerService->generateForQuestion($question, $request->user());
        } catch (NotConfigured $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        } catch (ProviderError $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Unable to generate answer. Please try again later.',
            ], 500);
        }

        $answer->load(['author', 'attachments']);
        $score = (int) $answer->votes()->sum('value');

        $payload = [
            'id' => $answer->id,
            'question_id' => $answer->question_id,
            'body_html' => $answer->body_html,
            'created_at' => $answer->created_at?->toIso8601String(),
            'author' => [
                'id' => $answer->author?->id,
                'name' => $answer->author?->name,
                'reputation' => $answer->author?->reputation ?? 0,
            ],
            'score' => $score,
            'is_accepted' => false,
            'ai_generated' => true,
            'attachments' => $answer->attachments->map(fn (Attachment $a) => [
                'id' => $a->id,
                'url' => $a->url,
                'original_name' => $a->original_name,
                'mime_type' => $a->mime_type,
                'size_bytes' => $a->size_bytes,
            ])->values()->all(),
            'comments' => [],
            'can' => [
                'update' => false,
                'delete' => $request->user()->can('delete', $answer),
                'vote' => true,
            ],
        ];

        NewAnswerPosted::dispatch($answer);

        return response()->json(['answer' => $payload]);
    }

}
