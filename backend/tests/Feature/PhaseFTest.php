<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Question;
use App\Models\User;
use App\Notifications\AnswerPostedOnYourQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PhaseFTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_comment_on_question_and_answer(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create();
        $answer = Answer::factory()->create(['question_id' => $question->id]);

        $response = $this->actingAs($member)->postJson(route('comments.store'), [
            'commentable_type' => 'question',
            'commentable_id' => $question->id,
            'body_markdown' => 'Question comment',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('comments', [
            'user_id' => $member->id,
            'commentable_type' => 'question',
            'commentable_id' => $question->id,
        ]);

        $response = $this->actingAs($member)->postJson(route('comments.store'), [
            'commentable_type' => 'answer',
            'commentable_id' => $answer->id,
            'body_markdown' => 'Answer comment',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('comments', [
            'user_id' => $member->id,
            'commentable_type' => 'answer',
            'commentable_id' => $answer->id,
        ]);
    }

    public function test_member_cannot_edit_or_delete_others_comment_but_moderator_can(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $other = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $comment = Comment::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->putJson(route('comments.update', $comment), ['body_markdown' => 'Changed'])
            ->assertForbidden();

        $this->actingAs($other)
            ->deleteJson(route('comments.destroy', $comment))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->putJson(route('comments.update', $comment), ['body_markdown' => 'Updated by mod'])
            ->assertOk();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body_markdown' => 'Updated by mod',
        ]);
    }

    public function test_bookmark_toggle_respects_unique_constraint(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create();

        $this->actingAs($user)->postJson(route('questions.bookmark', $question))->assertOk();
        $this->actingAs($user)->postJson(route('questions.bookmark', $question))->assertOk();

        $this->assertDatabaseCount('bookmarks', 1);

        $this->actingAs($user)->deleteJson(route('questions.bookmark.destroy', $question))->assertOk();
        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_notification_created_when_other_user_answers_question(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $responder = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $author->id]);

        Notification::fake();

        $this->actingAs($responder)->post(route('answers.store', $question), [
            'body_markdown' => 'Here is my answer',
        ])->assertRedirect();

        Notification::assertSentTo($author, AnswerPostedOnYourQuestion::class);
    }

    public function test_self_answer_does_not_create_notification(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $author->id]);

        Notification::fake();

        $this->actingAs($author)->post(route('answers.store', $question), [
            'body_markdown' => 'Self answer',
        ])->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_mark_as_read_and_unread_count_endpoint(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $user->id]);
        $answer = Answer::factory()->create(['question_id' => $question->id]);

        $user->notify(new AnswerPostedOnYourQuestion($answer));
        $notification = $user->notifications()->first();
        $this->assertNotNull($notification);

        $this->actingAs($user)->postJson(route('notifications.read', $notification->id))->assertOk();
        $notification->refresh();
        $this->assertNotNull($notification->read_at);

        $this->actingAs($user)
            ->getJson(route('notifications.unreadCount'))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);
    }
}
