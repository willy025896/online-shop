<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'locale' => ['required', Rule::in(config('app.supported_locales'))],
        ]);

        $request->session()->put('locale', $request->locale);

        if ($request->user()) {
            $request->user()->update(['locale' => $request->locale]);
        }

        return back();
    }
}
