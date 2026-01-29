<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Answer;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Category;
use App\Models\Question;
use App\Models\Tag;
use App\Queries\QuestionIndexQuery;
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
        $query = new QuestionIndexQuery($request);
        $questions = $query->paginate();

        return Inertia::render('Questions/Index', [
            'questions' => $questions,
            'filters' => $query->filters(),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'can' => [
                'create' => $request->user()->can('create', Question::class),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Questions/Create', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name']),
            'attachmentConfig' => $this->attachmentConfig(),
        ]);
    }

    public function store(StoreQuestionRequest $request)
    {
        $question = DB::transaction(function () use ($request): Question {
            $question = Question::create([
                'user_id' => $request->user()->id,
                'category_id' => $request->input('category_id'),
                'title' => $request->string('title')->toString(),
                'body_markdown' => $request->string('body_markdown')->toString(),
                'body_html' => $this->markdown->toHtml($request->string('body_markdown')->toString()),
            ]);

            $this->attachments->storeForQuestion(
                $question,
                $request->file('attachments', []),
                $request->user()
            );

            $question->tags()->sync($request->input('tags', []));

            return $question;
        });

        return redirect()
            ->route('questions.show', $question)
            ->with('success', 'Question created successfully.');
    }

    public function show(Request $request, Question $question): Response
    {
        $userId = $request->user()?->id;

        $question->load([
            'author',
            'category',
            'tags',
            'attachments',
            'comments.user',
            'bookmarks' => function ($bookmarkQuery) use ($userId) {
                if ($userId) {
                    $bookmarkQuery->where('user_id', $userId);
                } else {
                    $bookmarkQuery->whereRaw('1 = 0');
                }
            },
            'votes' => function ($query) use ($userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                }
            },
        ]);

        $question->loadSum('votes as score', 'value');

        $question->load(['answers' => function ($query) use ($userId) {
            $query->with([
                'author',
                'attachments',
                'comments.user',
                'votes' => function ($voteQuery) use ($userId) {
                    if ($userId) {
                        $voteQuery->where('user_id', $userId);
                    }
                },
            ])->withSum('votes as score', 'value')->orderBy('created_at');
        }]);

        $questionPayload = [
            'id' => $question->id,
            'title' => $question->title,
            'body_html' => $question->body_html ?: $this->markdown->toHtml($question->body_markdown),
            'created_at' => $question->created_at?->toIso8601String(),
            'author' => [
                'id' => $question->author?->id,
                'name' => $question->author?->name,
                'reputation' => $question->author?->reputation ?? 0,
            ],
            'category' => $question->category?->only(['id', 'name', 'slug']),
            'tags' => $question->tags->map(fn (Tag $tag) => $tag->only(['id', 'name', 'slug'])),
            'score' => $question->score,
            'current_user_vote' => $question->votes->first()?->value,
            'accepted_answer_id' => $question->accepted_answer_id,
            'attachments' => $question->attachments->map(fn (Attachment $attachment) => $this->attachmentPayload($attachment)),
            'bookmarks_count' => $question->bookmarks()->count(),
            'is_bookmarked' => $question->bookmarks->isNotEmpty(),
            'comments' => $question->comments->map(fn (Comment $comment) => $this->commentPayload($comment, $request->user())),
            'can' => [
                'update' => $request->user()->can('update', $question),
                'delete' => $request->user()->can('delete', $question),
                'vote' => $request->user()->can('vote', $question),
                'accept' => $request->user()->can('accept', $question),
            ],
        ];

        $answers = $question->answers->map(function ($answer) use ($request, $question) {
            return [
                'id' => $answer->id,
                'body_html' => $answer->body_html ?: $this->markdown->toHtml($answer->body_markdown),
                'created_at' => $answer->created_at?->toIso8601String(),
                'author' => [
                    'id' => $answer->author?->id,
                    'name' => $answer->author?->name,
                    'reputation' => $answer->author?->reputation ?? 0,
                ],
                'score' => $answer->score,
                'current_user_vote' => $answer->votes->first()?->value,
                'is_accepted' => $answer->id === $question->accepted_answer_id,
                'attachments' => $answer->attachments->map(fn (Attachment $attachment) => $this->attachmentPayload($attachment)),
                'comments' => $answer->comments->map(fn (Comment $comment) => $this->commentPayload($comment, $request->user())),
                'can' => [
                    'update' => $request->user()->can('update', $answer),
                    'delete' => $request->user()->can('delete', $answer),
                    'vote' => $request->user()->can('vote', $answer),
                ],
            ];
        });

        return Inertia::render('Questions/Show', [
            'question' => $questionPayload,
            'answers' => $answers,
            'can' => [
                'answer' => $request->user()->can('create', [Answer::class, $question]),
                'comment' => $request->user()->can('create', Comment::class),
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
                'category_id' => $question->category_id,
                'attachments' => $question->attachments->map(fn (Attachment $attachment) => $this->attachmentPayload($attachment)),
                'tag_ids' => $question->tags()->pluck('tags.id')->all(),
            ],
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name']),
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
                'category_id' => $request->input('category_id'),
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

            $question->tags()->sync($request->input('tags', []));
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

    private function commentPayload(Comment $comment, $currentUser): array
    {
        return [
            'id' => $comment->id,
            'body_html' => $comment->body_html ?: $this->markdown->toHtml($comment->body_markdown),
            'body_markdown' => $comment->body_markdown,
            'created_at' => $comment->created_at?->toIso8601String(),
            'author' => $comment->user?->only(['id', 'name']),
            'can' => [
                'update' => $currentUser?->can('update', $comment) ?? false,
                'delete' => $currentUser?->can('delete', $comment) ?? false,
            ],
        ];
    }
}
