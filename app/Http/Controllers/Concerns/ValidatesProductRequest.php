<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;

/**
 * Shared product field validation rules, used by Seller\ProductController's
 * store/update (Laravel Request validation) and the CSV import path (plain
 * Validator::make per row) so the rule set is only defined once.
 */
trait ValidatesProductRequest
{
    private function productValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0|max:9999999.99',
            'compare_price' => 'nullable|numeric|min:0|max:9999999.99',
            'stock' => 'required|integer|min:0',
            'status' => ['required', 'in:'.implode(',', [Product::STATUS_DRAFT, Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
            'is_featured' => 'boolean',
        ];
    }
}
