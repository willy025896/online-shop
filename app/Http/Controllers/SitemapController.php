<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(Request $request)
    {
        $xml = Cache::remember('sitemap.xml.'.$request->getHost(), now()->addHour(), fn () => view('sitemap', [
            'urls' => $this->urls(),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function urls()
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => now()],
            ['loc' => route('shops.index'), 'lastmod' => now()],
        ]);

        Product::active()
            ->whereHas('shop', fn ($query) => $query->where('status', Shop::STATUS_APPROVED))
            ->select('id', 'slug', 'updated_at')->chunkById(500, fn ($products) => $products->each(
                fn ($product) => $urls->push([
                    'loc' => route('products.show', $product->slug),
                    'lastmod' => $product->updated_at,
                ])
            ));

        Shop::where('status', Shop::STATUS_APPROVED)->select('id', 'slug', 'updated_at')->chunkById(500, fn ($shops) => $shops->each(
            fn ($shop) => $urls->push([
                'loc' => route('shops.show', $shop->slug),
                'lastmod' => $shop->updated_at,
            ])
        ));

        Category::active()->select('id', 'slug', 'updated_at')->chunkById(500, fn ($categories) => $categories->each(
            fn ($category) => $urls->push([
                'loc' => route('categories.show', $category->slug),
                'lastmod' => $category->updated_at,
            ])
        ));

        return $urls;
    }
}
