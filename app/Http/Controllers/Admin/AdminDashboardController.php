<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Services\HomeyApiService;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $dashboards = Dashboard::with('items')->orderBy('sort_order')->get();
        return view('admin.dashboards.index', compact('dashboards'));
    }

    public function create()
    {
        return view('admin.dashboards.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $dashboard = Dashboard::create($validated);
        return redirect()->route('admin.dashboards.edit', $dashboard)
            ->with('success', 'Dashboard created successfully');
    }

    public function edit(Dashboard $dashboard, HomeyApiService $homey)
    {
        $dashboard->load('items');
        $devices = $homey->isConfigured() ? $homey->getCachedDevices() : [];
        $flows = $homey->isConfigured() ? $homey->getCachedFlows() : [];

        return view('admin.dashboards.edit', compact('dashboard', 'devices', 'flows'));
    }

    public function update(Request $request, Dashboard $dashboard)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $dashboard->update($validated);
        return back()->with('success', 'Dashboard updated successfully');
    }

    public function destroy(Dashboard $dashboard)
    {
        $dashboard->delete();
        return redirect()->route('admin.dashboards.index')
            ->with('success', 'Dashboard deleted successfully');
    }
}
