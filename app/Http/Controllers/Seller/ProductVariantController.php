<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Concerns\ValidatesVariantRequest;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductVariantService;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    use ValidatesVariantRequest;

    public function __construct(
        private ProductVariantService $variantService,
    ) {}

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate($this->variantSyncValidationRules());

        $this->variantService->sync($product, $validated['options'], $validated['variants']);

        return back()->with('success', 'Variants updated.');
    }
}
