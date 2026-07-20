<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Shared canonical-URL logic for paginated/filterable listing pages
 * (home/products, shop index/show, category show).
 */
trait BuildsCanonicalListingUrl
{
    /**
     * Canonical URL for a listing page: keeps `page` (real pagination —
     * distinct content) but drops search/category/sort/rating/price query
     * params, since those are the same underlying content re-ordered or
     * narrowed, not separate pages worth indexing individually.
     */
    protected function canonicalListingUrl(string $routeName, array $routeParams, Request $request): string
    {
        $page = $request->integer('page');

        if ($page > 1) {
            $routeParams['page'] = $page;
        }

        return route($routeName, $routeParams);
    }
}
