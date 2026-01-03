<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Services\HomeyApiService;

class DashboardController extends Controller
{
    public function show(Dashboard $dashboard, HomeyApiService $homey)
    {
        if (!$dashboard->is_active) {
            abort(404);
        }

        $dashboard->load(['items' => function ($query) {
            $query->where('is_active', true)->orderBy('sort_order');
        }]);

        return view('dashboard.show', compact('dashboard'));
    }
}
