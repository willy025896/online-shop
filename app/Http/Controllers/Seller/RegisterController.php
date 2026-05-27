<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function create()
    {
        if (auth()->user()->shop) {
            return redirect()->route('seller.dashboard');
        }

        return Inertia::render('Seller/Register');
    }

    public function store(Request $request)
    {
        if (auth()->user()->shop) {
            return redirect()->route('seller.dashboard');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:shops,slug|alpha_dash',
            'description' => 'nullable|string|max:1000',
        ]);

        $shop = Shop::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'slug' => Str::lower($validated['slug']),
            'description' => $validated['description'] ?? null,
            'status' => Shop::STATUS_PENDING,
        ]);

        auth()->user()->update(['role' => User::ROLE_SELLER]);

        return redirect()->route('seller.dashboard')
            ->with('success', 'Shop registration submitted. Please wait for approval.');
    }
}
