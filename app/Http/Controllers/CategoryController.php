<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function show(Category $category)
    {
        abort_unless($category->is_active, 404);

        $categoryIds = collect([$category->id])
            ->merge($category->children->pluck('id'));

        $products = Product::active()
            ->whereIn('category_id', $categoryIds)
            ->with(['shop', 'primaryImage'])
            ->latest()
            ->paginate(12);

        return Inertia::render('Categories/Show', [
            'category' => $category->load('children', 'parent'),
            'products' => $products,
        ]);
    }
}
