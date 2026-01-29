<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhaseETest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_categories_and_tags(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $createCategory = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Team Ops',
            'description' => 'Operations',
            'parent_id' => null,
        ]);
        $createCategory->assertRedirect(route('admin.categories.index'));
        $category = Category::where('name', 'Team Ops')->first();
        $this->assertNotNull($category);

        $updateCategory = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Team Operations',
            'description' => 'Ops work',
            'parent_id' => null,
        ]);
        $updateCategory->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Team Operations']);

        $child = Category::factory()->create(['parent_id' => $category->id]);
        $deleteBlocked = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));
        $deleteBlocked->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id]);

        $tagResponse = $this->actingAs($admin)->post(route('admin.tags.store'), [
            'name' => 'Releases',
        ]);
        $tagResponse->assertRedirect(route('admin.tags.index'));
        $tag = Tag::where('name', 'Releases')->first();
        $this->assertNotNull($tag);

        $this->actingAs($admin)->put(route('admin.tags.update', $tag), [
            'name' => 'Release Mgmt',
        ])->assertRedirect(route('admin.tags.index'));
        $this->assertDatabaseHas('tags', ['name' => 'Release Mgmt']);

        $question = Question::factory()->create();
        $question->tags()->attach($tag->id);
        $this->actingAs($admin)->delete(route('admin.tags.destroy', $tag))->assertRedirect(route('admin.tags.index'));
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('question_tag', ['question_id' => $question->id, 'tag_id' => $tag->id]);
    }

    public function test_non_admin_cannot_access_admin_taxonomy(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member)->get(route('admin.categories.index'))->assertStatus(403);
        $this->actingAs($member)->get(route('admin.tags.index'))->assertStatus(403);
    }

    public function test_member_can_assign_category_and_tags_to_own_question(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $payload = [
            'title' => 'How to deploy safely?',
            'body_markdown' => 'Details about deployment.',
            'category_id' => $category->id,
            'tags' => $tags->take(2)->pluck('id')->all(),
        ];

        $response = $this->actingAs($member)->post(route('questions.store'), $payload);
        $response->assertRedirect();

        $question = Question::first();
        $this->assertEquals($category->id, $question->category_id);
        $this->assertCount(2, $question->tags);
    }

    public function test_invalid_tag_ids_are_rejected(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $response = $this->actingAs($member)->post(route('questions.store'), [
            'title' => 'Invalid tags',
            'body_markdown' => 'Body',
            'tags' => [999],
        ]);

        $response->assertSessionHasErrors(['tags.0']);
    }

    public function test_category_filter_returns_matching_questions(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $catA = Category::factory()->create();
        $catB = Category::factory()->create();

        $qA = Question::factory()->create(['category_id' => $catA->id]);
        $qB = Question::factory()->create(['category_id' => $catB->id]);

        $response = $this->actingAs($user)->get(route('questions.index', ['category' => $catA->id]));

        $response->assertOk();
        $response->assertSee($qA->title);
        $response->assertDontSee($qB->title);
    }

    public function test_tag_filter_and_status_filter_work(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $tagA = Tag::factory()->create();
        $tagB = Tag::factory()->create();

        $withBothAnswered = Question::factory()->create();
        Answer::factory()->create(['question_id' => $withBothAnswered->id]);
        $withBothAnswered->tags()->sync([$tagA->id, $tagB->id]);
        $withAOnly = Question::factory()->create();
        $withAOnly->tags()->sync([$tagA->id]);

        $response = $this->actingAs($user)->get(route('questions.index', [
            'tags' => [$tagA->id, $tagB->id],
            'status' => 'answered',
        ]));

        $response->assertOk();
        $response->assertSee($withBothAnswered->title);
        $response->assertDontSee($withAOnly->title);
    }

    public function test_date_filter_limits_results(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $recent = Question::factory()->create(['created_at' => now()->subDays(2)]);
        $older = Question::factory()->create(['created_at' => now()->subDays(20)]);

        $response = $this->actingAs($user)->get(route('questions.index', [
            'from' => now()->subDays(5)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee($recent->title);
        $response->assertDontSee($older->title);
    }

    public function test_search_matches_question_and_answer(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $matchTitle = Question::factory()->create(['title' => 'Deploy pipelines overview']);
        $answerQuestion = Question::factory()->create(['title' => 'Misc question']);
        Answer::factory()->create([
            'question_id' => $answerQuestion->id,
            'body_markdown' => 'This includes observability stack tips.',
        ]);

        $titleResponse = $this->actingAs($user)->get(route('questions.index', ['q' => 'pipelines']));
        $titleResponse->assertOk();
        $titleResponse->assertSee($matchTitle->title);

        $answerResponse = $this->actingAs($user)->get(route('questions.index', ['q' => 'observability']));
        $answerResponse->assertOk();
        $answerResponse->assertSee($answerQuestion->title);

        $emptyResponse = $this->actingAs($user)->get(route('questions.index', ['q' => '']));
        $emptyResponse->assertStatus(200);
    }
}
