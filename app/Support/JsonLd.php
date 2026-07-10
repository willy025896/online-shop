<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Shop;

class JsonLd
{
    public static function product(Product $product, string $description): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $description,
            'url' => route('products.show', $product->slug),
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->shop->name,
            ],
        ];

        if ($product->category) {
            $node['category'] = $product->category->name;
        }

        if ($product->images->isNotEmpty()) {
            $node['image'] = $product->images->map(fn ($image) => asset('storage/'.$image->path))->all();
        }

        $inStock = $product->variants->isNotEmpty()
            ? $product->variants->contains(fn ($variant) => $variant->inStock())
            : $product->inStock();
        $availability = $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';

        $node['offers'] = $product->variants->isNotEmpty() ? [
            '@type' => 'AggregateOffer',
            'priceCurrency' => Product::CURRENCY,
            'lowPrice' => (float) $product->variants->min('price'),
            'highPrice' => (float) $product->variants->max('price'),
            'offerCount' => $product->variants->count(),
            'availability' => $availability,
        ] : [
            '@type' => 'Offer',
            'priceCurrency' => Product::CURRENCY,
            'price' => (float) $product->price,
            'availability' => $availability,
        ];

        if ($product->reviews_count > 0) {
            $node['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $product->averageRating(),
                'reviewCount' => $product->reviews_count,
            ];
        }

        return $node;
    }

    public static function organization(Shop $shop, string $description): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $shop->name,
            'description' => $description,
            'url' => route('shops.show', $shop->slug),
        ];

        if ($shop->logo_path) {
            $node['logo'] = asset('storage/'.$shop->logo_path);
        }

        if ($shop->reviews_count > 0) {
            $node['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $shop->averageRating(),
                'reviewCount' => $shop->reviews_count,
            ];
        }

        return $node;
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public static function breadcrumbList(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }
}
