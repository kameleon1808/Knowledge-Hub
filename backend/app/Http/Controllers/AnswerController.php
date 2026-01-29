<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnswerRequest;
use App\Http\Requests\UpdateAnswerRequest;
use App\Models\Answer;
use App\Models\Attachment;
use App\Models\Question;
use App\Notifications\AnswerPostedOnYourQuestion;
use App\Services\AttachmentService;
use App\Services\MarkdownService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnswerController extends Controller
{
    public function __construct(
        private readonly AttachmentService $attachments,
        private readonly MarkdownService $markdown
    ) {
    }

    public function store(StoreAnswerRequest $request, Question $question)
    {
        $this->authorize('create', [Answer::class, $question]);

        $answer = DB::transaction(function () use ($request, $question): Answer {
            $answer = Answer::create([
                'question_id' => $question->id,
                'user_id' => $request->user()->id,
                'body_markdown' => $request->string('body_markdown')->toString(),
                'body_html' => $this->markdown->toHtml($request->string('body_markdown')->toString()),
            ]);

            $this->attachments->storeForAnswer(
                $answer,
                $request->file('attachments', []),
                $request->user()
            );

            return $answer;
        });

        if ($question->user_id !== $request->user()->id) {
            $question->author?->notify(new AnswerPostedOnYourQuestion($answer));
        }

        return redirect()
            ->route('questions.show', $question)
            ->with('success', 'Answer posted successfully.');
    }

    public function edit(Answer $answer): Response
    {
        $this->authorize('update', $answer);

        $answer->load(['question', 'attachments']);

        return Inertia::render('Answers/Edit', [
            'answer' => [
                'id' => $answer->id,
                'body_markdown' => $answer->body_markdown,
                'attachments' => $answer->attachments->map(fn (Attachment $attachment) => $this->attachmentPayload($attachment)),
            ],
            'question' => [
                'id' => $answer->question->id,
                'title' => $answer->question->title,
            ],
            'attachmentConfig' => $this->attachmentConfig(),
        ]);
    }

    public function update(UpdateAnswerRequest $request, Answer $answer)
    {
        $this->authorize('update', $answer);

        DB::transaction(function () use ($request, $answer): void {
            $answer->update([
                'body_markdown' => $request->string('body_markdown')->toString(),
                'body_html' => $this->markdown->toHtml($request->string('body_markdown')->toString()),
            ]);

            $this->attachments->deleteByIds(
                $answer,
                $request->input('remove_attachments', [])
            );

            $this->attachments->storeForAnswer(
                $answer,
                $request->file('attachments', []),
                $request->user()
            );
        });

        return redirect()
            ->route('questions.show', $answer->question_id)
            ->with('success', 'Answer updated successfully.');
    }

    public function destroy(Answer $answer)
    {
        $this->authorize('delete', $answer);

        DB::transaction(function () use ($answer): void {
            $this->attachments->deleteForAttachable($answer);
            $answer->delete();
        });

        return redirect()
            ->route('questions.show', $answer->question_id)
            ->with('success', 'Answer deleted successfully.');
    }

    private function attachmentConfig(): array
    {
        return [
            'maxSizeKb' => config('attachments.max_size_kb', 5120),
            'allowedMimes' => config('attachments.allowed_mimes', []),
        ];
    }

    private function attachmentPayload(Attachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'url' => $attachment->url,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size_bytes' => $attachment->size_bytes,
        ];
    }
}
