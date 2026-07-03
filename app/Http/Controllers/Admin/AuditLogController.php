<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AdminActionLog::with('admin:id,name')
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('admin_id'), fn ($q) => $q->where('admin_id', $request->integer('admin_id')))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['action', 'admin_id']),
            'actionOptions' => fn () => AdminActionLog::select('action')->distinct()->orderBy('action')->pluck('action'),
            'adminOptions' => fn () => User::where('role', User::ROLE_ADMIN)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
