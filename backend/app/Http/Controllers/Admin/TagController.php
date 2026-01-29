<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use App\Models\Tag;
use App\Services\SlugGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function __construct(private readonly SlugGenerator $slugs)
    {
    }

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $tagsQuery = Tag::query()->select(['id', 'name', 'slug', 'created_at']);

        if ($search !== '') {
            $tagsQuery->where(function ($query) use ($search, $likeOperator) {
                $query->where('name', $likeOperator, "%{$search}%")
                    ->orWhere('slug', $likeOperator, "%{$search}%");
            });
        }

        return Inertia::render('Admin/Tags/Index', [
            'tags' => $tagsQuery->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Tags/Create');
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $tag = new Tag();
            $tag->name = $request->string('name')->toString();
            $tag->slug = $this->slugs->generate($tag, $tag->name);
            $tag->save();
        });

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag created.');
    }

    public function edit(Tag $tag): Response
    {
        return Inertia::render('Admin/Tags/Edit', [
            'tag' => $tag->only(['id', 'name', 'slug']),
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        DB::transaction(function () use ($request, $tag): void {
            $tag->name = $request->string('name')->toString();
            $tag->slug = $this->slugs->generate($tag, $tag->name, $tag->id);
            $tag->save();
        });

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag updated.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        DB::transaction(function () use ($tag): void {
            $tag->questions()->detach();
            $tag->delete();
        });

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag deleted.');
    }
}
