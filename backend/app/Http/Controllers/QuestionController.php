<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Answer;
use App\Models\Attachment;
use App\Models\Question;
use App\Services\AttachmentService;
use App\Services\MarkdownService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function __construct(
        private readonly AttachmentService $attachments,
        private readonly MarkdownService $markdown
    ) {
        $this->authorizeResource(Question::class, 'question');
    }

    public function index(Request $request): Response
    {
        $questions = Question::query()
            ->with('author')
            ->latest()
            ->paginate(10)
            ->through(fn (Question $question) => [
                'id' => $question->id,
                'title' => $question->title,
                'created_at' => $question->created_at?->toIso8601String(),
                'author' => [
                    'id' => $question->author?->id,
                    'name' => $question->author?->name,
                ],
            ]);

        return Inertia::render('Questions/Index', [
            'questions' => $questions,
            'can' => [
                'create' => $request->user()->can('create', Question::class),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Questions/Create', [
            'attachmentConfig' => $this->attachmentConfig(),
        ]);
    }

    public function store(StoreQuestionRequest $request)
    {
        $question = DB::transaction(function () use ($request): Question {
            $question = Question::create([
                'user_id' => $request->user()->id,
                'title' => $request->string('title')->toString(),
                'body_markdown' => $request->string('body_markdown')->toString(),
                'body_html' => $this->markdown->toHtml($request->string('body_markdown')->toString()),
            ]);

            $this->attachments->storeForQuestion(
                $question,
                $request->file('attachments', []),
                $request->user()
            );

            return $question;
        });

        return redirect()
            ->route('questions.show', $question)
            ->with('success', 'Question created successfully.');
    }

    public function show(Request $request, Question $question): Response
    {
        $question->load([
            'author',
            'attachments',
            'answers' => function ($query) {
                $query->with(['author', 'attachments'])->orderBy('created_at');
            },
        ]);

        $questionPayload = [
            'id' => $question->id,
            'title' => $question->title,
            'body_html' => $question->body_html ?: $this->markdown->toHtml($question->body_markdown),
            'created_at' => $question->created_at?->toIso8601String(),
            'author' => [
                'id' => $question->author?->id,
                'name' => $question->author?->name,
            ],
            'attachments' => $question->attachments->map(fn (Attachment $attachment) => $this->attachmentPayload($attachment)),
            'can' => [
                'update' => $request->user()->can('update', $question),
                'delete' => $request->user()->can('delete', $question),
            ],
        ];

        $answers = $question->answers->map(function ($answer) use ($request) {
            return [
                'id' => $answer->id,
                'body_html' => $answer->body_html ?: $this->markdown->toHtml($answer->body_markdown),
                'created_at' => $answer->created_at?->toIso8601String(),
                'author' => [
                    'id' => $answer->author?->id,
                    'name' => $answer->author?->name,
                ],
                'attachments' => $answer->attachments->map(fn (Attachment $attachment) => $this->attachmentPayload($attachment)),
                'can' => [
                    'update' => $request->user()->can('update', $answer),
                    'delete' => $request->user()->can('delete', $answer),
                ],
            ];
        });

        return Inertia::render('Questions/Show', [
            'question' => $questionPayload,
            'answers' => $answers,
            'can' => [
                'answer' => $request->user()->can('create', [Answer::class, $question]),
            ],
            'attachmentConfig' => $this->attachmentConfig(),
        ]);
    }

    public function edit(Question $question): Response
    {
        $question->load('attachments');

        return Inertia::render('Questions/Edit', [
            'question' => [
                'id' => $question->id,
                'title' => $question->title,
                'body_markdown' => $question->body_markdown,
                'attachments' => $question->attachments->map(fn (Attachment $attachment) => $this->attachmentPayload($attachment)),
            ],
            'attachmentConfig' => $this->attachmentConfig(),
        ]);
    }

    public function update(UpdateQuestionRequest $request, Question $question)
    {
        DB::transaction(function () use ($request, $question): void {
            $question->update([
                'title' => $request->string('title')->toString(),
                'body_markdown' => $request->string('body_markdown')->toString(),
                'body_html' => $this->markdown->toHtml($request->string('body_markdown')->toString()),
            ]);

            $this->attachments->deleteByIds(
                $question,
                $request->input('remove_attachments', [])
            );

            $this->attachments->storeForQuestion(
                $question,
                $request->file('attachments', []),
                $request->user()
            );
        });

        return redirect()
            ->route('questions.show', $question)
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question)
    {
        DB::transaction(function () use ($question): void {
            $this->attachments->deleteForQuestion($question);
            $question->delete();
        });

        return redirect()
            ->route('questions.index')
            ->with('success', 'Question deleted successfully.');
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
