<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'locale' => ['required', 'in:en,zh_TW'],
        ]);

        $request->session()->put('locale', $request->locale);

        return back();
    }
}
