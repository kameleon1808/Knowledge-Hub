<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Answer;
use App\Models\Comment;
use App\Models\Question;
use App\Services\MarkdownService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function __construct(private readonly MarkdownService $markdown)
    {
    }

    public function store(StoreCommentRequest $request): JsonResponse
    {
        $commentable = $this->resolveCommentable($request->string('commentable_type'), $request->integer('commentable_id'));

        $this->authorize('create', Comment::class);

        DB::transaction(function () use ($request, $commentable): void {
            $commentable->comments()->create([
                'user_id' => $request->user()->id,
                'body_markdown' => $request->string('body_markdown')->toString(),
                'body_html' => $this->markdown->toHtml($request->string('body_markdown')->toString()),
            ]);
        });

        return response()->json([
            'comments' => $this->commentPayloads($commentable, $request->user()),
        ], 201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        DB::transaction(function () use ($request, $comment): void {
            $comment->update([
                'body_markdown' => $request->string('body_markdown')->toString(),
                'body_html' => $this->markdown->toHtml($request->string('body_markdown')->toString()),
            ]);
        });

        return response()->json([
            'comments' => $this->commentPayloads($comment->commentable, $request->user()),
        ]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $commentable = $comment->commentable;

        DB::transaction(function () use ($comment): void {
            $comment->delete();
        });

        return response()->json([
            'comments' => $this->commentPayloads($commentable, request()->user()),
        ]);
    }

    private function resolveCommentable(string $type, int $id): Model
    {
        $map = [
            'question' => Question::class,
            'answer' => Answer::class,
        ];

        /** @var class-string<Model> $model */
        $model = $map[$type] ?? Question::class;

        return $model::query()->with('comments.user')->findOrFail($id);
    }

    private function commentPayloads(Model $commentable, $currentUser): array
    {
        $commentable->loadMissing(['comments.user']);

        return $commentable->comments->map(fn (Comment $comment) => $this->commentPayload($comment, $currentUser))->all();
    }

    private function commentPayload(Comment $comment, $currentUser): array
    {
        return [
            'id' => $comment->id,
            'body_html' => $comment->body_html ?: $this->markdown->toHtml($comment->body_markdown),
            'body_markdown' => $comment->body_markdown,
            'created_at' => $comment->created_at?->toIso8601String(),
            'author' => $comment->user?->only(['id', 'name']) ?? null,
            'can' => [
                'update' => $currentUser?->can('update', $comment) ?? false,
                'delete' => $currentUser?->can('delete', $comment) ?? false,
            ],
        ];
    }
}
