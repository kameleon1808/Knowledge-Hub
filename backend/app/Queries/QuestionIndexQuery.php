<?php

namespace App\Queries;

use App\Models\Question;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QuestionIndexQuery
{
    public function __construct(private readonly Request $request)
    {
    }

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        $filters = $this->filters();

        $query = Question::query()
            ->select('questions.*')
            ->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->withCount(['answers', 'bookmarks'])
            ->withSum('votes as score', 'value');

        $userId = $this->request->user()?->id;

        if ($userId) {
            $query->withExists(['bookmarks as is_bookmarked' => function ($bookmarkQuery) use ($userId) {
                $bookmarkQuery->where('user_id', $userId);
            }]);
        }

        $this->applyCategoryFilter($query, $filters['category_id']);
        $this->applyTagFilter($query, $filters['tags']);
        $this->applyStatusFilter($query, $filters['status']);
        $this->applyDateFilter($query, $filters['from'], $filters['to']);
        $this->applySearch($query, $filters['q']);

        if ($filters['q'] !== null && $filters['q'] !== '') {
            $query->orderByDesc('relevance')->orderByDesc('questions.created_at');
        } else {
            $query->orderByDesc('questions.created_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function filters(): array
    {
        $preset = $this->request->string('date_preset')->toString();
        [$from, $to] = $this->dateRange($preset);

        $manualFrom = $this->request->date('from');
        $manualTo = $this->request->date('to');

        if ($manualFrom) {
            $from = $manualFrom;
        }

        if ($manualTo) {
            $to = $manualTo;
        }

        return [
            'q' => $this->request->string('q')->trim()->toString(),
            'category_id' => $this->request->integer('category') ?: null,
            'tags' => $this->normalizeTags($this->request->input('tags', [])),
            'status' => $this->request->string('status')->trim()->toString(),
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'date_preset' => $preset,
        ];
    }

    private function normalizeTags(array $tags): array
    {
        return collect($tags)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    private function applyCategoryFilter(Builder $query, ?int $categoryId): void
    {
        if ($categoryId) {
            $query->where('questions.category_id', $categoryId);
        }
    }

    private function applyTagFilter(Builder $query, array $tagIds): void
    {
        if (count($tagIds) === 0) {
            return;
        }

        $query->whereHas('tags', function ($tagQuery) use ($tagIds) {
            $tagQuery->whereIn('tags.id', $tagIds);
        }, '=', count($tagIds));
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'answered') {
            $query->whereHas('answers');
        }

        if ($status === 'unanswered') {
            $query->whereDoesntHave('answers');
        }
    }

    private function applyDateFilter(Builder $query, ?string $from, ?string $to): void
    {
        if ($from) {
            $query->whereDate('questions.created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('questions.created_at', '<=', $to);
        }
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            $tsQuery = "websearch_to_tsquery('english', ? )";

            $query->where(function ($searchQuery) use ($tsQuery, $search) {
                $searchQuery->whereRaw("questions.search_vector @@ {$tsQuery}", [$search])
                    ->orWhereExists(function ($sub) use ($tsQuery, $search) {
                        $sub->selectRaw('1')
                            ->from('answers')
                            ->whereColumn('answers.question_id', 'questions.id')
                            ->whereRaw("answers.search_vector @@ {$tsQuery}", [$search]);
                    });
            });

            $query->selectRaw(
                "ts_rank(questions.search_vector, {$tsQuery}) + coalesce((select max(ts_rank(answers.search_vector, {$tsQuery})) from answers where answers.question_id = questions.id), 0) as relevance",
                [$search, $search]
            );
        } else {
            $like = "%{$search}%";
            $query->where(function ($searchQuery) use ($like) {
                $searchQuery->where('questions.title', 'like', $like)
                    ->orWhere('questions.body_markdown', 'like', $like)
                    ->orWhereExists(function ($sub) use ($like) {
                        $sub->selectRaw('1')
                            ->from('answers')
                            ->whereColumn('answers.question_id', 'questions.id')
                            ->where('answers.body_markdown', 'like', $like);
                    });
            });

            $query->selectRaw('1 as relevance');
        }
    }

    private function dateRange(string $preset): array
    {
        $now = Carbon::now();

        return match ($preset) {
            'last7' => [$now->copy()->subDays(7), $now],
            'last30' => [$now->copy()->subDays(30), $now],
            'last90' => [$now->copy()->subDays(90), $now],
            default => [null, null],
        };
    }
}
