<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Services\AddressService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AddressController extends Controller
{
    public function __construct(
        private AddressService $addressService,
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('Addresses/Index', [
            'addresses' => $this->addressService->listForUser($request->user()),
        ]);
    }

    public function create()
    {
        return Inertia::render('Addresses/Create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFields($request);

        $this->addressService->create($request->user(), $validated);

        return redirect()->route('addresses.index')
            ->with('success', 'Address saved.');
    }

    public function edit(Address $address)
    {
        $this->authorize('update', $address);

        return Inertia::render('Addresses/Edit', [
            'address' => $address,
        ]);
    }

    public function update(Request $request, Address $address)
    {
        $this->authorize('update', $address);

        $validated = $this->validatedFields($request);

        $this->addressService->update($address, $validated);

        return redirect()->route('addresses.index')
            ->with('success', 'Address updated.');
    }

    public function destroy(Address $address)
    {
        $this->authorize('delete', $address);

        $this->addressService->delete($address);

        return back()->with('success', 'Address deleted.');
    }

    public function setDefault(Address $address)
    {
        $this->authorize('update', $address);

        $this->addressService->setDefault($address);

        return back()->with('success', 'Default address updated.');
    }

    private function validatedFields(Request $request): array
    {
        return $request->validate([
            'label' => 'nullable|string|max:50',
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'is_default' => 'boolean',
        ]);
    }
}
