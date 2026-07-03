<?php

namespace App\Services;

use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Single entry point for writing admin audit trail entries. Called explicitly
 * from each Admin controller's mutating action (see ADR-009) — unlike
 * OrderStatusLog, this spans several unrelated model types (User, Shop,
 * Category, Coupon), so an event-based approach would need bespoke wiring
 * per model anyway; an explicit call at the point of action is more direct.
 */
class AdminAuditLogger
{
    public function log(User $admin, string $action, Model $subject, array $changes = []): void
    {
        AdminActionLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'changes' => $changes,
        ]);
    }
}
