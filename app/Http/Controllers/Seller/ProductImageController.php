<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $request->validate([
            'images' => 'required|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $currentCount = $product->images()->count();
        if ($currentCount + count($request->file('images')) > 5) {
            return back()->withErrors(['images' => 'Maximum 5 images per product.']);
        }

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store("products/{$product->id}", 'public');

            $product->images()->create([
                'path' => $path,
                'sort_order' => $currentCount + $index,
            ]);
        }

        return back()->with('success', 'Images uploaded.');
    }

    public function destroy(ProductImage $image)
    {
        $this->authorize('update', $image->product);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Image deleted.');
    }
}
