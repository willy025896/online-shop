<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single entry point for a seller's product options/variants edits. Options
 * not present in $optionsInput are hard-deleted (structural config, not
 * order history). Variants not present in $variantsInput are soft-deleted
 * (ProductVariant uses SoftDeletes) so historical cart_items/order_items
 * keep a valid product_variant_id — see ADR-011.
 */
class ProductVariantService
{
    public function sync(Product $product, array $optionsInput, array $variantsInput): void
    {
        DB::transaction(function () use ($product, $optionsInput, $variantsInput) {
            $valueIdByKey = $this->syncOptions($product, $optionsInput);
            $this->syncVariants($product, $variantsInput, $valueIdByKey);
        });
    }

    /**
     * @return array<string, int> map of each option value's client-supplied "key" to its persisted id
     */
    private function syncOptions(Product $product, array $optionsInput): array
    {
        // Preloaded once so the per-option/per-value loop below matches ids
        // in memory instead of issuing a find() query per row.
        $existingOptions = $product->options()->with('values')->get()->keyBy('id');

        $valueIdByKey = [];
        $keptOptionIds = [];

        foreach ($optionsInput as $optionData) {
            $option = isset($optionData['id']) ? $existingOptions->get($optionData['id']) : null;

            $attributes = [
                'name' => $optionData['name'],
                'sort_order' => $optionData['sort_order'] ?? 0,
            ];

            $option = $option
                ? tap($option)->update($attributes)
                : $product->options()->create($attributes);

            $keptOptionIds[] = $option->id;
            $keptValueIds = [];
            $existingValues = $option->relationLoaded('values') ? $option->values->keyBy('id') : collect();

            foreach ($optionData['values'] as $valueData) {
                $value = isset($valueData['id']) ? $existingValues->get($valueData['id']) : null;

                $valueAttributes = [
                    'value' => $valueData['value'],
                    'sort_order' => $valueData['sort_order'] ?? 0,
                ];

                $value = $value
                    ? tap($value)->update($valueAttributes)
                    : $option->values()->create($valueAttributes);

                $valueIdByKey[$valueData['key']] = $value->id;
                $keptValueIds[] = $value->id;
            }

            $option->values()->whereNotIn('id', $keptValueIds)->delete();
        }

        $product->options()->whereNotIn('id', $keptOptionIds)->delete();

        return $valueIdByKey;
    }

    /**
     * @param  array<string, int>  $valueIdByKey
     */
    private function syncVariants(Product $product, array $variantsInput, array $valueIdByKey): void
    {
        // Preloaded once so the per-variant loop below matches ids/skus in
        // memory instead of issuing a find() + a SKU-uniqueness query per row.
        $existingVariants = $product->variants()->get()->keyBy('id');
        $variantsBySku = ProductVariant::withTrashed()
            ->whereIn('sku', array_column($variantsInput, 'sku'))
            ->get(['id', 'sku'])
            ->groupBy('sku');

        $seenCombinations = [];
        $keptVariantIds = [];

        foreach ($variantsInput as $variantData) {
            $valueIds = array_map(
                function (string $key) use ($valueIdByKey) {
                    if (! isset($valueIdByKey[$key])) {
                        throw ValidationException::withMessages([
                            'variants' => "Unknown option value reference: {$key}",
                        ]);
                    }

                    return $valueIdByKey[$key];
                },
                $variantData['option_value_keys'],
            );

            sort($valueIds);
            $combinationKey = implode('-', $valueIds);

            if (isset($seenCombinations[$combinationKey])) {
                throw ValidationException::withMessages([
                    'variants' => 'Duplicate option combination in variants.',
                ]);
            }
            $seenCombinations[$combinationKey] = true;

            $variant = isset($variantData['id']) ? $existingVariants->get($variantData['id']) : null;

            // Wildcard array validation can't express a per-row unique-ignoring-self
            // rule, so SKU uniqueness (product_variants.sku is globally unique) is
            // checked here instead of in ValidatesVariantRequest.
            $skuTaken = $variantsBySku->get($variantData['sku'], collect())
                ->contains(fn ($row) => $variant === null || $row->id !== $variant->id);

            if ($skuTaken) {
                throw ValidationException::withMessages([
                    'variants' => "SKU already in use: {$variantData['sku']}",
                ]);
            }

            $attributes = [
                'sku' => $variantData['sku'],
                'price' => $variantData['price'],
                'compare_price' => $variantData['compare_price'] ?? null,
                'stock' => $variantData['stock'],
            ];

            $variant = $variant
                ? tap($variant)->update($attributes)
                : $product->variants()->create($attributes);

            $variant->optionValues()->sync($valueIds);
            $keptVariantIds[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keptVariantIds)->delete();
    }
}
