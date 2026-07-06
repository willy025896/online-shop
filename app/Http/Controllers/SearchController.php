<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SearchQuery;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggestions(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $products = [];
        if ($query !== '') {
            $products = Product::query()
                ->active()
                ->with('shop')
                ->whereFullText(['name', 'description'], $query)
                ->limit(8)
                ->get(['id', 'name', 'slug', 'shop_id'])
                ->map(fn ($product) => [
                    'type' => 'product',
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'shop_name' => $product->shop?->name,
                ])
                ->filter(fn ($product) => $product['shop_name'] !== null)
                ->values();
        }

        $hotQueries = SearchQuery::query()
            ->orderByDesc('count')
            ->limit(6)
            ->pluck('query')
            ->toArray();

        return response()->json([
            'products' => $products,
            'hot_queries' => $hotQueries,
        ]);
    }
}
