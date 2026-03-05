<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function edit()
    {
        return Inertia::render('Seller/Shop/Edit', [
            'shop' => auth()->user()->shop,
        ]);
    }

    public function update(Request $request)
    {
        $shop = auth()->user()->shop;
        $this->authorize('update', $shop);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($shop->logo_path) {
                Storage::disk('public')->delete($shop->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('shops', 'public');
        }

        unset($validated['logo']);
        $shop->update($validated);

        return back()->with('success', 'Shop updated.');
    }
}
