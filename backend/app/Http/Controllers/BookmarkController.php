<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookmarkController extends Controller
{
    public function index(Request $request): Response
    {
        $bookmarks = $request->user()->bookmarkedQuestions()
            ->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->withCount(['answers', 'bookmarks'])
            ->withSum('votes as score', 'value')
            ->orderByDesc('pivot_created_at')
            ->paginate(10)
            ->through(function (Question $question) use ($request) {
                return [
                    'id' => $question->id,
                    'title' => $question->title,
                    'created_at' => $question->created_at?->toIso8601String(),
                    'author' => $question->author?->only(['id', 'name']),
                    'category' => $question->category?->only(['id', 'name', 'slug']),
                    'tags' => $question->tags->map->only(['id', 'name', 'slug']),
                    'answers_count' => $question->answers_count,
                    'bookmarks_count' => $question->bookmarks_count,
                    'is_bookmarked' => true,
                    'score' => (int) $question->score,
                ];
            });

        return Inertia::render('Bookmarks/Index', [
            'bookmarks' => $bookmarks,
        ]);
    }

    public function store(Question $question): JsonResponse
    {
        $user = request()->user();

        $this->authorize('create', Bookmark::class);

        DB::transaction(function () use ($user, $question): void {
            Bookmark::firstOrCreate([
                'user_id' => $user->id,
                'question_id' => $question->id,
            ]);
        });

        return $this->payload($question, true);
    }

    public function destroy(Question $question): JsonResponse
    {
        $user = request()->user();

        $bookmark = Bookmark::query()
            ->where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();

        if ($bookmark) {
            $this->authorize('delete', $bookmark);
            DB::transaction(fn () => $bookmark->delete());
        }

        return $this->payload($question, false);
    }

    private function payload(Question $question, bool $bookmarked): JsonResponse
    {
        $count = $question->bookmarks()->count();

        return response()->json([
            'bookmarked' => $bookmarked,
            'bookmarks_count' => $count,
        ]);
    }
}
