<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Attachment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionAnswerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_question(): void
    {
        Storage::fake('public');

        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $response = $this->actingAs($member)->post(route('questions.store'), [
            'title' => 'How do we share project context?',
            'body_markdown' => 'We need a consistent way to share updates.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('questions', [
            'title' => 'How do we share project context?',
            'user_id' => $member->id,
        ]);
    }

    public function test_member_cannot_edit_or_delete_others_question(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $intruder = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($intruder)->put(route('questions.update', $question), [
            'title' => 'Unauthorized update',
            'body_markdown' => 'Should not work.',
        ]);

        $response->assertForbidden();

        $response = $this->actingAs($intruder)->delete(route('questions.destroy', $question));

        $response->assertForbidden();
    }

    public function test_moderator_can_edit_and_delete_any_question(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($moderator)->put(route('questions.update', $question), [
            'title' => 'Updated by moderator',
            'body_markdown' => 'Updated body.',
        ]);

        $response->assertRedirect(route('questions.show', $question));
        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'title' => 'Updated by moderator',
        ]);

        $response = $this->actingAs($moderator)->delete(route('questions.destroy', $question));

        $response->assertRedirect(route('questions.index'));
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    public function test_member_can_answer_question(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create();

        $response = $this->actingAs($member)->post(route('answers.store', $question), [
            'body_markdown' => 'Here is a possible answer.',
        ]);

        $response->assertRedirect(route('questions.show', $question));
        $this->assertDatabaseHas('answers', [
            'question_id' => $question->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_member_cannot_edit_or_delete_others_answer(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $intruder = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $answer = Answer::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($intruder)->put(route('answers.update', $answer), [
            'body_markdown' => 'Unauthorized update',
        ]);

        $response->assertForbidden();

        $response = $this->actingAs($intruder)->delete(route('answers.destroy', $answer));

        $response->assertForbidden();
    }

    public function test_image_upload_validation_rejects_non_images(): void
    {
        Storage::fake('public');

        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $file = UploadedFile::fake()->create('not-image.pdf', 120, 'application/pdf');

        $response = $this->actingAs($member)->post(route('questions.store'), [
            'title' => 'Upload test',
            'body_markdown' => 'Testing invalid upload.',
            'attachments' => [$file],
        ]);

        $response->assertSessionHasErrors('attachments.0');
    }

    public function test_image_upload_accepts_images(): void
    {
        Storage::fake('public');

        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($member)->post(route('questions.store'), [
            'title' => 'Upload image',
            'body_markdown' => 'Testing valid upload.',
            'attachments' => [$file],
        ]);

        $response->assertRedirect();

        $attachment = Attachment::first();
        $this->assertNotNull($attachment);
        Storage::disk('public')->assertExists($attachment->path);
    }

    public function test_deleting_question_removes_answers_and_attachments(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $question = Question::factory()->create(['user_id' => $owner->id]);
        $answer = Answer::factory()->create([
            'question_id' => $question->id,
            'user_id' => $owner->id,
        ]);

        $questionPath = "questions/{$question->id}/question.jpg";
        Storage::disk('public')->put($questionPath, 'fake');
        $question->attachments()->create([
            'user_id' => $owner->id,
            'disk' => 'public',
            'path' => $questionPath,
            'original_name' => 'question.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 4,
        ]);

        $answerPath = "answers/{$answer->id}/answer.jpg";
        Storage::disk('public')->put($answerPath, 'fake');
        $answer->attachments()->create([
            'user_id' => $owner->id,
            'disk' => 'public',
            'path' => $answerPath,
            'original_name' => 'answer.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 4,
        ]);

        $response = $this->actingAs($owner)->delete(route('questions.destroy', $question));

        $response->assertRedirect(route('questions.index'));
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('answers', ['id' => $answer->id]);
        $this->assertDatabaseCount('attachments', 0);
        Storage::disk('public')->assertMissing($questionPath);
        Storage::disk('public')->assertMissing($answerPath);
    }
}
