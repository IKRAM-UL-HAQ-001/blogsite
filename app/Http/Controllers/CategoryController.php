<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Category::query()
            ->with('parent')
            ->withCount(['articles', 'children'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search');

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('parent')) {
            $request->string('parent') === 'root'
                ? $query->whereNull('parent_id')
                : $query->where('parent_id', $request->integer('parent'));
        }

        return view('admin.categories.index', [
            'categories' => $query->paginate(12)->withQueryString(),
            'parents' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category(),
            'parents' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::query()->create($this->validatedPayload($request));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(Category $category): View
    {
        $category->load(['parent', 'children'])->loadCount(['articles', 'children']);

        $recentArticles = $category->articles()
            ->latest('articles.created_at')
            ->take(8)
            ->get();

        return view('admin.categories.show', compact('category', 'recentArticles'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parents' => Category::query()
                ->where('id', '!=', $category->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($this->validatedPayload($request));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->children()->update(['parent_id' => null]);
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function validatedPayload(CategoryRequest $request): array
    {
        $payload = $request->validated();
        $payload['slug'] = $payload['slug'] ?: Str::slug($payload['name']);
        $payload['parent_id'] = $payload['parent_id'] ?? null;

        return $payload;
    }
}
