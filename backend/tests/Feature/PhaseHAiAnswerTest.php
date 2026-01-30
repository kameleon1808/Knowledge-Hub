<?php

namespace Tests\Feature;

use App\Jobs\GenerateAiAnswerForQuestion;
use App\Models\AiAuditLog;
use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhaseHAiAnswerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function createAiAssistant(): User
    {
        return User::factory()->create([
            'email' => User::AI_ASSISTANT_EMAIL,
            'name' => 'AI Assistant',
            'role' => User::ROLE_MEMBER,
            'is_system' => true,
        ]);
    }

    public function test_non_owner_cannot_generate_ai_answer(): void
    {
        $this->createAiAssistant();
        Config::set('ai.enabled', true);
        Config::set('ai.provider', 'openai');
        Config::set('ai.providers.openai.key', 'test-key');

        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $other = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->postJson(route('questions.ai-answer', $question));

        $response->assertForbidden();
        $this->assertDatabaseCount('answers', 0);
    }

    public function test_question_owner_can_generate_ai_answer(): void
    {
        $this->createAiAssistant();
        Config::set('ai.enabled', true);
        Config::set('ai.provider', 'openai');
        Config::set('ai.providers.openai.key', 'test-key');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'id' => 'chatcmpl-1',
                'object' => 'chat.completion',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Here is a suggested answer in **markdown**.',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                    'total_tokens' => 30,
                ],
            ], 200),
        ]);

        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->postJson(route('questions.ai-answer', $question));

        $response->assertOk();
        $response->assertJsonPath('answer.ai_generated', true);
        $this->assertDatabaseHas('answers', [
            'question_id' => $question->id,
            'ai_generated' => true,
        ]);
    }

    public function test_admin_can_generate_ai_answer_for_any_question(): void
    {
        $this->createAiAssistant();
        Config::set('ai.enabled', true);
        Config::set('ai.provider', 'openai');
        Config::set('ai.providers.openai.key', 'test-key');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'id' => 'chatcmpl-1',
                'object' => 'chat.completion',
                'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'Admin can trigger AI.'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 10, 'total_tokens' => 15],
            ], 200),
        ]);

        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $question = Question::factory()->create(['user_id' => $member->id]);

        $response = $this->actingAs($admin)->postJson(route('questions.ai-answer', $question));

        $response->assertOk();
        $this->assertDatabaseHas('answers', ['question_id' => $question->id, 'ai_generated' => true]);
    }

    public function test_ai_disabled_returns_friendly_error_and_no_answer(): void
    {
        $this->createAiAssistant();
        Config::set('ai.enabled', false);

        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->postJson(route('questions.ai-answer', $question));

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'AI features are disabled. Enable AI in configuration to generate answers.']);
        $this->assertDatabaseCount('answers', 0);
    }

    public function test_provider_missing_key_returns_503_and_creates_audit_error(): void
    {
        $this->createAiAssistant();
        Config::set('ai.enabled', true);
        Config::set('ai.provider', 'openai');
        Config::set('ai.providers.openai.key', null);

        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->postJson(route('questions.ai-answer', $question));

        $response->assertStatus(503);
        $this->assertDatabaseCount('answers', 0);
        $this->assertDatabaseHas('ai_audit_logs', [
            'subject_type' => Question::class,
            'subject_id' => $question->id,
            'status' => 'error',
        ]);
        $log = AiAuditLog::where('subject_id', $question->id)->first();
        $this->assertNotNull($log->error_message);
    }

    public function test_success_creates_ai_audit_log_with_tokens_and_payloads(): void
    {
        $this->createAiAssistant();
        Config::set('ai.enabled', true);
        Config::set('ai.provider', 'openai');
        Config::set('ai.providers.openai.key', 'test-key');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'id' => 'chatcmpl-1',
                'object' => 'chat.completion',
                'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'Audited answer.'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50, 'total_tokens' => 150],
            ], 200),
        ]);

        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->postJson(route('questions.ai-answer', $question));

        $log = AiAuditLog::where('subject_id', $question->id)->where('status', 'success')->first();
        $this->assertNotNull($log);
        $this->assertSame(100, $log->input_tokens);
        $this->assertSame(50, $log->output_tokens);
        $this->assertSame(150, $log->total_tokens);
        $this->assertIsArray($log->request_payload);
        $this->assertArrayHasKey('messages', $log->request_payload);
        $this->assertIsArray($log->response_payload);
        $this->assertStringContainsString('Audited answer.', $log->response_text ?? '');
    }

    public function test_provider_error_creates_audit_log_with_error_message(): void
    {
        $this->createAiAssistant();
        Config::set('ai.enabled', true);
        Config::set('ai.provider', 'openai');
        Config::set('ai.providers.openai.key', 'test-key');

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'Rate limit exceeded']], 429),
        ]);

        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->postJson(route('questions.ai-answer', $question));

        $log = AiAuditLog::where('subject_id', $question->id)->where('status', 'error')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Rate limit', $log->error_message ?? '');
    }

    public function test_auto_answer_job_does_not_create_duplicate_ai_answers(): void
    {
        $this->createAiAssistant();
        Config::set('ai.enabled', true);
        Config::set('ai.auto_answer', true);
        Config::set('ai.provider', 'openai');
        Config::set('ai.providers.openai.key', 'test-key');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'id' => 'chatcmpl-1',
                'object' => 'chat.completion',
                'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'Only one.'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 10, 'total_tokens' => 15],
            ], 200),
        ]);

        $question = Question::factory()->create();
        $aiUser = User::where('email', User::AI_ASSISTANT_EMAIL)->first();

        $job = new GenerateAiAnswerForQuestion($question);
        $job->handle(app(\App\Services\AiAnswerService::class));

        $count = Answer::where('question_id', $question->id)->where('ai_generated', true)->count();
        $this->assertSame(1, $count);

        $job->handle(app(\App\Services\AiAnswerService::class));

        $count = Answer::where('question_id', $question->id)->where('ai_generated', true)->count();
        $this->assertSame(1, $count);
    }
}
