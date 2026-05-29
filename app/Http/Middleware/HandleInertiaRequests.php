<?php

namespace App\Http\Middleware;

use App\Models\Message;
use App\Services\CartService;
use App\Services\WishlistService;
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
            'nav' => Lang::get('navigation'),
            'locale' => app()->getLocale(),
            'cartCount' => fn () => app(CartService::class)->getCartCount(),
            'wishlistProductIds' => fn () => app(WishlistService::class)->favoritedProductIds(),
            'unreadMessageCount' => fn () => $this->getUnreadMessageCount($request),
            'unreadNotificationCount' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
            'recentNotifications' => fn () => $request->user()?->notifications()->limit(10)->get() ?? collect(),
            'notificationBellLang' => fn () => Lang::get('notifications'),
            'userRole' => fn () => $request->user()?->role,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }

    private function getUnreadMessageCount(Request $request): int
    {
        $user = $request->user();
        if (! $user) {
            return 0;
        }

        return Message::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->whereHas('conversation', function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                    ->orWhere('seller_user_id', $user->id);
            })
            ->count();
    }
}
