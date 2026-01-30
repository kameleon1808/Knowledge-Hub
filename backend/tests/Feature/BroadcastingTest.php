<?php

namespace Tests\Feature;

use App\Events\NewAnswerPosted;
use App\Events\NotificationCreated;
use App\Events\VoteUpdated;
use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BroadcastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_answer_posted_is_broadcast_to_question_channel(): void
    {
        Event::fake([NewAnswerPosted::class]);

        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $member->id]);

        $this->actingAs($member)->post(route('answers.store', $question), [
            'body_markdown' => 'New answer body',
        ]);

        Event::assertDispatched(NewAnswerPosted::class, function (NewAnswerPosted $event) use ($question): bool {
            return $event->answer->question_id === $question->id;
        });
    }

    public function test_vote_updated_is_broadcast_when_vote_cast(): void
    {
        Event::fake([VoteUpdated::class]);

        $author = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $voter = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $author->id]);

        $this->actingAs($voter)->postJson(route('votes.store'), [
            'votable_type' => 'question',
            'votable_id' => $question->id,
            'value' => 1,
        ])->assertOk();

        Event::assertDispatched(VoteUpdated::class, function (VoteUpdated $event) use ($question): bool {
            return $event->questionId === $question->id
                && $event->votableType === 'question'
                && $event->votableId === $question->id
                && $event->newScore === 1;
        });
    }

    public function test_vote_updated_is_broadcast_when_vote_removed(): void
    {
        Event::fake([VoteUpdated::class]);

        $author = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $voter = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $author->id]);
        $answer = Answer::factory()->create(['question_id' => $question->id, 'user_id' => $author->id]);

        $this->actingAs($voter)->postJson(route('votes.store'), [
            'votable_type' => 'answer',
            'votable_id' => $answer->id,
            'value' => 1,
        ])->assertOk();

        $this->actingAs($voter)->deleteJson(route('votes.destroy'), [
            'votable_type' => 'answer',
            'votable_id' => $answer->id,
        ])->assertOk();

        Event::assertDispatched(VoteUpdated::class, 2);
    }

    public function test_notification_created_is_broadcast_to_user_channel_when_answer_posted_on_their_question(): void
    {
        Event::fake([NotificationCreated::class]);

        $questionAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $answerAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $questionAuthor->id]);

        $this->actingAs($answerAuthor)->post(route('answers.store', $question), [
            'body_markdown' => 'Answer on your question',
        ]);

        Event::assertDispatched(NotificationCreated::class, function (NotificationCreated $event) use ($questionAuthor): bool {
            return $event->userId === $questionAuthor->id
                && $event->unreadCount >= 1;
        });
    }

    public function test_user_cannot_authorize_another_users_notification_channel(): void
    {
        $userA = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $userB = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $response = $this->actingAs($userA)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.'.$userB->id.'.notifications',
            'socket_id' => '123.456',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_authorize_own_notification_channel(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $response = $this->actingAs($user)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.'.$user->id.'.notifications',
            'socket_id' => '123.456',
        ]);

        $response->assertSuccessful();
    }
}
