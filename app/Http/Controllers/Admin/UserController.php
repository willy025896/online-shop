<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(15);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:' . implode(',', [User::ROLE_CUSTOMER, User::ROLE_SELLER, User::ROLE_ADMIN])],
        ]);

        $user->update($validated);

        return back()->with('success', 'User role updated.');
    }
}
