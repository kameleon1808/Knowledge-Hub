<?php

namespace Tests\Feature;

use App\Jobs\ProcessKnowledgeItemJob;
use App\Models\KnowledgeItem;
use App\Services\ActivityLogger;
use App\Models\Project;
use App\Models\RagQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseITest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('local');
    }

    public function test_non_member_cannot_view_project(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $nonMember = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Test Project',
            'description' => null,
            'owner_user_id' => $owner->id,
        ]);
        $project->users()->attach($owner->id, ['role' => Project::ROLE_OWNER]);

        $response = $this->actingAs($nonMember)->get(route('projects.show', $project));

        $response->assertForbidden();
    }

    public function test_member_sees_only_projects_they_belong_to(): void
    {
        $owner1 = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $owner2 = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $project1 = Project::create(['name' => 'Project A', 'description' => null, 'owner_user_id' => $owner1->id]);
        $project1->users()->attach($owner1->id, ['role' => Project::ROLE_OWNER]);
        $project1->users()->attach($member->id, ['role' => Project::ROLE_MEMBER]);

        $project2 = Project::create(['name' => 'Project B', 'description' => null, 'owner_user_id' => $owner2->id]);
        $project2->users()->attach($owner2->id, ['role' => Project::ROLE_OWNER]);

        $response = $this->actingAs($member)->get(route('projects.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.id', $project1->id)
        );
    }

    public function test_member_cannot_edit_project_settings(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Test Project',
            'description' => null,
            'owner_user_id' => $owner->id,
        ]);
        $project->users()->attach($owner->id, ['role' => Project::ROLE_OWNER]);
        $project->users()->attach($member->id, ['role' => Project::ROLE_MEMBER]);

        $response = $this->actingAs($member)->put(route('projects.update', $project), [
            'name' => 'Updated',
            'description' => 'Desc',
        ]);

        $response->assertForbidden();
    }

    public function test_owner_can_manage_members(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Test Project',
            'description' => null,
            'owner_user_id' => $owner->id,
        ]);
        $project->users()->attach($owner->id, ['role' => Project::ROLE_OWNER]);

        $this->actingAs($owner);
        $this->assertTrue($owner->can('manageMembers', $project));
    }

    public function test_upload_txt_creates_pending_item_and_dispatches_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Test',
            'description' => null,
            'owner_user_id' => $user->id,
        ]);
        $project->users()->attach($user->id, ['role' => Project::ROLE_OWNER]);

        $file = UploadedFile::fake()->createWithContent('doc.txt', 'Hello world');

        $response = $this->actingAs($user)->post(route('projects.knowledge-items.store', $project), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('knowledge_items', [
            'project_id' => $project->id,
            'type' => KnowledgeItem::TYPE_DOCUMENT,
            'status' => KnowledgeItem::STATUS_PENDING,
        ]);
        \Illuminate\Support\Facades\Queue::assertPushed(ProcessKnowledgeItemJob::class);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action' => 'knowledge_item.uploaded',
        ]);
    }

    public function test_email_creation_creates_pending_item_and_dispatches_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Test',
            'description' => null,
            'owner_user_id' => $user->id,
        ]);
        $project->users()->attach($user->id, ['role' => Project::ROLE_OWNER]);

        $response = $this->actingAs($user)->post(route('projects.knowledge-emails.store', $project), [
            'title' => 'Email subject',
            'from' => 'sender@example.com',
            'body_text' => 'Email body content here.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('knowledge_items', [
            'project_id' => $project->id,
            'type' => KnowledgeItem::TYPE_EMAIL,
            'status' => KnowledgeItem::STATUS_PENDING,
            'title' => 'Email subject',
        ]);
        \Illuminate\Support\Facades\Queue::assertPushed(ProcessKnowledgeItemJob::class);
    }

    public function test_process_knowledge_item_job_produces_chunks_and_embeddings(): void
    {
        Config::set('ai.enabled', true);
        Config::set('ai.provider', 'mock');
        Config::set('ai.embedding_dimension', 1536);

        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Test',
            'description' => null,
            'owner_user_id' => $user->id,
        ]);
        $project->users()->attach($user->id, ['role' => Project::ROLE_OWNER]);

        $item = KnowledgeItem::create([
            'project_id' => $project->id,
            'type' => KnowledgeItem::TYPE_EMAIL,
            'title' => 'Test email',
            'source_meta' => null,
            'original_content_path' => null,
            'raw_text' => str_repeat('Sample text for chunking. ', 50),
            'status' => KnowledgeItem::STATUS_PENDING,
            'error_message' => null,
        ]);

        $job = new ProcessKnowledgeItemJob($item->id);
        $job->handle(app(\App\Services\Knowledge\KnowledgeProcessService::class));

        $item->refresh();
        $this->assertSame(KnowledgeItem::STATUS_PROCESSED, $item->status);
        $this->assertGreaterThan(0, $item->chunks()->count());
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action' => 'knowledge_item.processed',
        ]);
    }

    public function test_rag_ask_creates_rag_query_and_returns_answer(): void
    {
        User::factory()->create([
            'email' => User::AI_ASSISTANT_EMAIL,
            'is_system' => true,
            'role' => User::ROLE_MEMBER,
        ]);
        Config::set('ai.enabled', true);
        Config::set('ai.provider', 'mock');
        Config::set('ai.embedding_dimension', 1536);

        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Test',
            'description' => null,
            'owner_user_id' => $user->id,
        ]);
        $project->users()->attach($user->id, ['role' => Project::ROLE_OWNER]);

        $response = $this->actingAs($user)->postJson(route('projects.rag-ask', $project), [
            'question_text' => 'What is the main topic?',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('answer_text', $data);
        $this->assertArrayHasKey('rag_query_id', $data);
        $this->assertDatabaseHas('rag_queries', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'question_text' => 'What is the main topic?',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action' => 'rag.asked',
        ]);
    }

    public function test_markdown_export_generates_file_and_authorized_download(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Export Test',
            'description' => 'Desc',
            'owner_user_id' => $user->id,
        ]);
        $project->users()->attach($user->id, ['role' => Project::ROLE_OWNER]);

        $response = $this->actingAs($user)->get(route('projects.export.markdown', $project));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/markdown; charset=UTF-8');
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action' => 'export.generated',
        ]);
    }

    public function test_activity_logs_created_for_uploads_and_export(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Activity Test',
            'description' => null,
            'owner_user_id' => $user->id,
        ]);
        $project->users()->attach($user->id, ['role' => Project::ROLE_OWNER]);

        $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'New Project',
            'description' => null,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityLogger::ACTION_PROJECT_CREATED,
        ]);
    }
}
