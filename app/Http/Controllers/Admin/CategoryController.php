<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AdminAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __construct(private AdminAuditLogger $auditLogger) {}

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
            $validated['slug'] .= '-'.Str::random(4);
        }

        $category = Category::create($validated);

        $this->auditLogger->log($request->user(), 'category.created', $category, [
            'name' => $category->name,
        ]);

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

        $this->auditLogger->log($request->user(), 'category.updated', $category, Arr::except($category->getChanges(), 'updated_at'));

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Request $request, Category $category)
    {
        if ($category->children()->count() > 0) {
            throw ValidationException::withMessages([
                'category' => 'Cannot delete category with subcategories.',
            ]);
        }

        $name = $category->name;
        $category->delete();

        $this->auditLogger->log($request->user(), 'category.deleted', $category, [
            'name' => $name,
        ]);

        return back()->with('success', 'Category deleted.');
    }
}
