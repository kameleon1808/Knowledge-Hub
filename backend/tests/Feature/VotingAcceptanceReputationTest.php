<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotingAcceptanceReputationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upvote_question_and_reputation_updates(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $voter = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($voter)->postJson(route('votes.store'), [
            'votable_type' => 'question',
            'votable_id' => $question->id,
            'value' => 1,
        ]);

        $response->assertOk()->assertJson([
            'score' => 1,
            'current_user_vote' => 1,
        ]);

        $this->assertDatabaseHas('votes', [
            'user_id' => $voter->id,
            'votable_type' => 'question',
            'votable_id' => $question->id,
            'value' => 1,
        ]);

        $this->assertSame(5, $author->refresh()->reputation);
    }

    public function test_user_can_switch_vote_and_reputation_adjusts(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $voter = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $author->id]);

        $this->actingAs($voter)->postJson(route('votes.store'), [
            'votable_type' => 'question',
            'votable_id' => $question->id,
            'value' => 1,
        ]);

        $this->assertSame(5, $author->refresh()->reputation);

        $response = $this->actingAs($voter)->postJson(route('votes.store'), [
            'votable_type' => 'question',
            'votable_id' => $question->id,
            'value' => -1,
        ]);

        $response->assertOk()->assertJson([
            'score' => -1,
            'current_user_vote' => -1,
        ]);

        $this->assertSame(-2, $author->refresh()->reputation);
    }

    public function test_user_can_remove_vote_and_reputation_reverts(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $voter = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $author->id]);

        $this->actingAs($voter)->postJson(route('votes.store'), [
            'votable_type' => 'question',
            'votable_id' => $question->id,
            'value' => -1,
        ]);

        $this->assertSame(-2, $author->refresh()->reputation);

        $response = $this->actingAs($voter)->deleteJson(route('votes.destroy'), [
            'votable_type' => 'question',
            'votable_id' => $question->id,
        ]);

        $response->assertOk()->assertJson([
            'score' => 0,
            'current_user_vote' => null,
        ]);

        $this->assertSame(0, $author->refresh()->reputation);
    }

    public function test_vote_unique_constraint_prevents_duplicates(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $voter = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $author->id]);

        Vote::create([
            'user_id' => $voter->id,
            'votable_type' => $question->getMorphClass(),
            'votable_id' => $question->id,
            'value' => 1,
        ]);

        $this->expectException(QueryException::class);

        Vote::create([
            'user_id' => $voter->id,
            'votable_type' => $question->getMorphClass(),
            'votable_id' => $question->id,
            'value' => 1,
        ]);
    }

    public function test_question_author_can_accept_answer(): void
    {
        $questionAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $answerAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $questionAuthor->id]);
        $answer = Answer::factory()->create([
            'question_id' => $question->id,
            'user_id' => $answerAuthor->id,
        ]);

        $response = $this->actingAs($questionAuthor)->postJson(
            route('questions.accept', [$question, $answer])
        );

        $response->assertOk()->assertJson([
            'accepted_answer_id' => $answer->id,
        ]);

        $this->assertSame($answer->id, $question->refresh()->accepted_answer_id);
        $this->assertSame(15, $answerAuthor->refresh()->reputation);
    }

    public function test_switching_accepted_answer_updates_reputation(): void
    {
        $questionAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $firstAnswerAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $secondAnswerAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $questionAuthor->id]);
        $firstAnswer = Answer::factory()->create([
            'question_id' => $question->id,
            'user_id' => $firstAnswerAuthor->id,
        ]);
        $secondAnswer = Answer::factory()->create([
            'question_id' => $question->id,
            'user_id' => $secondAnswerAuthor->id,
        ]);

        $this->actingAs($questionAuthor)->postJson(
            route('questions.accept', [$question, $firstAnswer])
        );

        $this->assertSame(15, $firstAnswerAuthor->refresh()->reputation);

        $this->actingAs($questionAuthor)->postJson(
            route('questions.accept', [$question, $secondAnswer])
        );

        $this->assertSame(0, $firstAnswerAuthor->refresh()->reputation);
        $this->assertSame(15, $secondAnswerAuthor->refresh()->reputation);
        $this->assertSame($secondAnswer->id, $question->refresh()->accepted_answer_id);
    }

    public function test_non_author_cannot_accept_answer(): void
    {
        $questionAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $intruder = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $answerAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $questionAuthor->id]);
        $answer = Answer::factory()->create([
            'question_id' => $question->id,
            'user_id' => $answerAuthor->id,
        ]);

        $response = $this->actingAs($intruder)->postJson(
            route('questions.accept', [$question, $answer])
        );

        $response->assertForbidden();
    }

    public function test_user_cannot_vote_on_own_post(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($author)->postJson(route('votes.store'), [
            'votable_type' => 'question',
            'votable_id' => $question->id,
            'value' => 1,
        ]);

        $response->assertForbidden();
    }
}
