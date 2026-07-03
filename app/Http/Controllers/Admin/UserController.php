<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(private AdminAuditLogger $auditLogger) {}

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
            'role' => ['required', 'in:'.implode(',', [User::ROLE_CUSTOMER, User::ROLE_SELLER, User::ROLE_ADMIN])],
        ]);

        $previousRole = $user->role;

        $user->role = $validated['role'];
        $user->save();

        if ($previousRole !== $user->role) {
            $this->auditLogger->log($request->user(), 'user.role_updated', $user, [
                'from' => $previousRole,
                'to' => $user->role,
            ]);
        }

        return back()->with('success', 'User role updated.');
    }
}
