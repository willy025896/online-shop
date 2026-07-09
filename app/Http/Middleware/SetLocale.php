<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Fall back to the authenticated user's persisted locale (e.g. a fresh
        // session on a new device/browser) before the app default, so a user
        // who has switched locale before doesn't see the page revert to
        // English just because this particular session never set it.
        $sessionLocale = $request->session()->get('locale');
        $locale = $sessionLocale ?? $request->user()?->locale ?? config('app.locale');

        if (in_array($locale, config('app.supported_locales'))) {
            App::setLocale($locale);

            if ($locale !== $sessionLocale) {
                $request->session()->put('locale', $locale);
            }
        }

        return $next($request);
    }
}
