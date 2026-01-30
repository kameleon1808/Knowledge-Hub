<?php

namespace App\Services;

use App\AI\DTO\ChatRequest;
use App\AI\LlmManager;
use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Config;

class AiAnswerService
{
    public function __construct(
        private readonly LlmManager $llm,
        private readonly MarkdownService $markdown
    ) {
    }

    /**
     * Generate a draft answer for the question using the configured LLM.
     * Creates an Answer attributed to the AI Assistant user and an audit log entry.
     *
     * @throws \App\AI\Exceptions\NotConfigured
     * @throws \App\AI\Exceptions\ProviderError
     */
    public function generateForQuestion(Question $question, ?User $actor): Answer
    {
        $messages = $this->buildMessages($question);
        $model = $this->llm->defaultModel();
        $temperature = (float) Config::get('ai.temperature', 0.3);
        $maxTokens = (int) Config::get('ai.max_output_tokens', 700);

        $request = ChatRequest::make(
            $model,
            $messages,
            $temperature,
            $maxTokens,
            [
                'request_id' => uniqid('ai_', true),
                'user_id' => $actor?->id,
                'question_id' => $question->id,
            ]
        );

        $auditContext = [
            'user_id' => $actor?->id,
            'subject_type' => Question::class,
            'subject_id' => $question->id,
        ];

        $result = $this->llm->generateChatCompletion($request, $auditContext);
        $text = trim($result->response->text);

        $aiUser = User::aiAssistant();
        if ($aiUser === null) {
            throw new \RuntimeException(
                'AI Assistant system user is not seeded. Run php artisan db:seed to create it.'
            );
        }

        $bodyHtml = $this->markdown->toHtml($text);

        return Answer::create([
            'question_id' => $question->id,
            'user_id' => $aiUser->id,
            'body_markdown' => $text,
            'body_html' => $bodyHtml,
            'ai_generated' => true,
            'ai_audit_log_id' => $result->auditLog->id,
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->llm->isConfigured();
    }

    /**
     * Check if the question already has an AI-generated answer (for idempotency).
     */
    public function questionHasAiAnswer(Question $question): bool
    {
        return $question->answers()->where('ai_generated', true)->exists();
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(Question $question): array
    {
        $systemPrompt = 'You are a helpful assistant answering questions on a team knowledge base. '
            . 'Answer concisely. Use bullet points when helpful and code snippets only when relevant. '
            . 'If the question lacks context, ask one or two short clarifying questions. '
            . 'Respond in markdown.';

        $context = $this->questionContext($question);
        $userContent = "Question: {$question->title}\n\n{$question->body_markdown}";
        if ($context !== '') {
            $userContent .= "\n\nContext:\n" . $context;
        }

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userContent],
        ];
    }

    private function questionContext(Question $question): string
    {
        $parts = [];
        $question->loadMissing(['category', 'tags']);
        if ($question->category) {
            $parts[] = 'Category: ' . $question->category->name;
        }
        if ($question->tags->isNotEmpty()) {
            $parts[] = 'Tags: ' . $question->tags->pluck('name')->join(', ');
        }
        $recent = $question->answers()->orderByDesc('created_at')->limit(2)->get(['body_markdown']);
        if ($recent->isNotEmpty()) {
            $snippet = $recent->map(fn ($a) => \Illuminate\Support\Str::limit($a->body_markdown, 150))->join("\n");
            $parts[] = 'Existing answers (excerpts): ' . $snippet;
        }
        return implode("\n", $parts);
    }
}
