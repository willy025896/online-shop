<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('shop', 'category', 'primaryImage')
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
        ]);
    }
}
