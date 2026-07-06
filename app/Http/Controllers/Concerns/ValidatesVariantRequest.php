<?php

namespace App\Http\Controllers\Concerns;

/**
 * Validation rules for the whole-form options/variants sync payload
 * (Seller\ProductVariantController::update), mirroring the extraction
 * convention of ValidatesProductRequest.
 */
trait ValidatesVariantRequest
{
    private function variantSyncValidationRules(): array
    {
        return [
            'options' => 'present|array',
            'options.*.id' => 'nullable|integer',
            'options.*.name' => 'required|string|max:100',
            'options.*.sort_order' => 'nullable|integer|min:0',
            'options.*.values' => 'required|array|min:1',
            'options.*.values.*.id' => 'nullable|integer',
            'options.*.values.*.key' => 'required|string|max:100',
            'options.*.values.*.value' => 'required|string|max:100',
            'options.*.values.*.sort_order' => 'nullable|integer|min:0',

            'variants' => 'present|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.sku' => 'required|string|max:100',
            'variants.*.price' => 'required|numeric|min:0|max:9999999.99',
            'variants.*.compare_price' => 'nullable|numeric|min:0|max:9999999.99',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.option_value_keys' => 'required|array|min:1',
            'variants.*.option_value_keys.*' => 'required|string',
        ];
    }
}
