<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\SlugGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly SlugGenerator $slugs)
    {
    }

    public function index(): Response
    {
        $categories = Category::query()
            ->with('parent:id,name')
            ->withCount('children')
            ->orderByRaw('parent_id is null desc')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get([
                'id',
                'parent_id',
                'name',
                'slug',
                'description',
                'created_at',
            ]);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Create', [
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $category = new Category();
            $category->fill($request->validated());
            $category->slug = $this->slugs->generate($category, $request->string('name')->toString());
            $category->save();
        });

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created.');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category->only(['id', 'name', 'slug', 'description', 'parent_id']),
            'parents' => $this->parentOptions($category->id),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        DB::transaction(function () use ($request, $category): void {
            $category->fill($request->validated());
            $category->slug = $this->slugs->generate($category, $request->string('name')->toString(), $category->id);
            $category->save();
        });

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->count() > 0) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Cannot delete a category that has child categories. Move or remove children first.');
        }

        DB::transaction(function () use ($category): void {
            $category->questions()->update(['category_id' => null]);
            $category->delete();
        });

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted.');
    }

    private function parentOptions(?int $excludeId = null)
    {
        return Category::query()
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
