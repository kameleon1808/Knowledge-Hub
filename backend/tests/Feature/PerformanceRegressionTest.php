<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Category;
use App\Models\Comment;
use App\Models\KnowledgeItem;
use App\Models\Project;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PerformanceRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function captureQueryCount(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$response, $count];
    }

    public function test_questions_index_query_count_is_bounded(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $questions = Question::factory()->count(6)->create(['category_id' => $category->id]);
        foreach ($questions as $question) {
            $question->tags()->sync($tags->take(2)->pluck('id')->all());
            Answer::factory()->create(['question_id' => $question->id]);
        }

        [$response, $count] = $this->captureQueryCount(function () use ($user) {
            return $this->actingAs($user)->get(route('questions.index'));
        });

        $response->assertOk();
        $this->assertLessThanOrEqual(15, $count);
    }

    public function test_question_show_query_count_is_bounded(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $viewer = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $question = Question::factory()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
        ]);
        $question->tags()->sync($tags->pluck('id')->all());

        Comment::factory()->count(2)->create([
            'commentable_type' => Question::class,
            'commentable_id' => $question->id,
        ]);

        $answers = Answer::factory()->count(3)->create(['question_id' => $question->id]);
        foreach ($answers as $answer) {
            Comment::factory()->count(2)->forAnswer($answer)->create();
        }

        [$response, $count] = $this->captureQueryCount(function () use ($viewer, $question) {
            return $this->actingAs($viewer)->get(route('questions.show', $question));
        });

        $response->assertOk();
        $this->assertLessThanOrEqual(20, $count);
    }

    public function test_project_show_query_count_is_bounded(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Perf Project',
            'description' => 'Performance test project.',
            'owner_user_id' => $owner->id,
        ]);
        $project->users()->attach($owner->id, ['role' => Project::ROLE_OWNER]);

        KnowledgeItem::query()->create([
            'project_id' => $project->id,
            'type' => KnowledgeItem::TYPE_EMAIL,
            'title' => 'Sample email',
            'source_meta' => ['from' => 'team@example.com'],
            'original_content_path' => null,
            'raw_text' => 'Example email body.',
            'status' => KnowledgeItem::STATUS_PROCESSED,
            'error_message' => null,
        ]);

        [$response, $count] = $this->captureQueryCount(function () use ($owner, $project) {
            return $this->actingAs($owner)->get(route('projects.show', ['project' => $project->id, 'tab' => 'knowledge']));
        });

        $response->assertOk();
        $this->assertLessThanOrEqual(12, $count);
    }

    public function test_questions_index_payload_is_trimmed(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        Question::factory()->create();

        $this->actingAs($user)
            ->get(route('questions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Questions/Index')
                ->has('questions.data', 1)
                ->missing('questions.data.0.body_markdown')
                ->missing('questions.data.0.body_html')
            );
    }

    public function test_project_knowledge_items_are_paginated(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $project = Project::create([
            'name' => 'Knowledge Pagination',
            'description' => null,
            'owner_user_id' => $owner->id,
        ]);
        $project->users()->attach($owner->id, ['role' => Project::ROLE_OWNER]);

        for ($i = 0; $i < 20; $i++) {
            KnowledgeItem::query()->create([
                'project_id' => $project->id,
                'type' => KnowledgeItem::TYPE_DOCUMENT,
                'title' => 'Doc '.$i,
                'source_meta' => ['filename' => 'doc-'.$i.'.txt'],
                'original_content_path' => 'knowledge/'.$project->id.'/doc-'.$i.'.txt',
                'raw_text' => null,
                'status' => KnowledgeItem::STATUS_PROCESSED,
                'error_message' => null,
            ]);
        }

        $this->actingAs($owner)
            ->get(route('projects.show', ['project' => $project->id, 'tab' => 'knowledge']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Projects/Show')
                ->has('knowledgeItems.data', 15)
                ->has('knowledgeItems.links')
            );
    }
}
