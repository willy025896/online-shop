<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'dashboard_widgets' => 'required|array',
            'dashboard_widgets.revenue' => 'boolean',
            'dashboard_widgets.order_status' => 'boolean',
            'dashboard_widgets.top_products' => 'boolean',
            'dashboard_widgets.revenue_chart' => 'boolean',
        ]);

        $user = auth()->user();
        $prefs = $user->preferences ?? [];
        $prefs['dashboard_widgets'] = array_merge(
            DashboardController::DEFAULT_WIDGETS,
            $validated['dashboard_widgets']
        );
        $user->update(['preferences' => $prefs]);

        return back();
    }
}
