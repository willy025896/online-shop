<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $path = $request->path() == '/' ? 'index' : $request->path();
        $pathArray = explode('/', $path);
        $mainPage = $pathArray[0];

        return array_merge(parent::share($request), [
            'lang' => Lang::get($mainPage),
            'cartCount' => fn () => app(CartService::class)->getCartCount(),
            'userRole' => fn () => $request->user()?->role,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
