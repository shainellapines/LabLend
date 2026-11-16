<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for equipment categories (FR-C1).
 * This is the pattern every other CRUD module copies:
 *   Form Request validates -> controller saves -> redirect with a flash message.
 */
class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::search($request->query('search'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString(); // keeps ?search=... on the page 2, 3... links

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', "Category \"{$category->name}\" was created.");
    }

    // Route model binding: Laravel sees {category} in the URL and the Category type-hint,
    // loads that record automatically, and shows a 404 page if it does not exist.
    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', "Category \"{$category->name}\" was updated.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        // Step 1.5 adds: if ($category->equipment()->exists()) -> refuse with an error message (FR-C1).

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', "Category \"{$category->name}\" was deleted.");
    }
}
