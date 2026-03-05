<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('children', 'parent')
            ->root()
            ->orderBy('sort_order')
            ->get();

        $allCategories = Category::orderBy('sort_order')->get();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'allCategories' => $allCategories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] .= '-' . Str::random(4);
        }

        Category::create($validated);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validated['parent_id'] == $category->id) {
            return back()->withErrors(['parent_id' => 'Category cannot be its own parent.']);
        }

        $category->update($validated);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->count() > 0) {
            return back()->withErrors(['category' => 'Cannot delete category with subcategories.']);
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
