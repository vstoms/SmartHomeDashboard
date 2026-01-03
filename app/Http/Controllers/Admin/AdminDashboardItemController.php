<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\DashboardItem;
use App\Services\HomeyApiService;
use Illuminate\Http\Request;

class AdminDashboardItemController extends Controller
{
    public function store(Request $request, Dashboard $dashboard)
    {
        $validated = $request->validate([
            'type' => 'required|in:device,flow',
            'homey_id' => 'required|string',
            'name' => 'required|string|max:255',
            'capabilities' => 'nullable|array',
        ]);

        // Get device capabilities from Homey to store
        if ($validated['type'] === 'device') {
            $homey = new HomeyApiService();
            $device = $homey->getDevice($validated['homey_id']);
            if ($device && isset($device['capabilitiesObj'])) {
                $validated['capabilities'] = $device['capabilitiesObj'];
            }
        }

        $validated['is_active'] = true;
        $dashboard->items()->create($validated);
        return back()->with('success', 'Item added to dashboard');
    }

    public function settings(DashboardItem $item, HomeyApiService $homey)
    {
        $capabilities = [];

        if ($item->isDevice()) {
            // Get fresh capabilities from Homey
            $device = $homey->getDevice($item->homey_id);
            if ($device && isset($device['capabilitiesObj'])) {
                $capabilities = $device['capabilitiesObj'];
                // Update stored capabilities
                $item->update(['capabilities' => $capabilities]);
            } else {
                // Fall back to stored capabilities
                $capabilities = $item->capabilities ?? [];
            }
        }

        return view('admin.items.settings', compact('item', 'capabilities'));
    }

    public function update(Request $request, DashboardItem $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capabilities' => 'nullable|array',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'display_capabilities' => 'nullable|array',
            'show_toggle' => 'nullable',
            'show_dimmer' => 'nullable',
            'show_thermostat' => 'nullable',
        ]);

        // Build settings array
        $settings = $item->settings ?? [];
        $settings['display_capabilities'] = $request->input('display_capabilities', []);
        $settings['show_toggle'] = $request->has('show_toggle');
        $settings['show_dimmer'] = $request->has('show_dimmer');
        $settings['show_thermostat'] = $request->has('show_thermostat');

        $updateData = [
            'name' => $validated['name'],
            'settings' => $settings,
        ];

        // Only update is_active if explicitly provided in the request
        if ($request->has('is_active')) {
            $updateData['is_active'] = true;
        }

        $item->update($updateData);

        return redirect()->route('admin.dashboards.edit', $item->dashboard)
            ->with('success', 'Item settings updated');
    }

    public function destroy(DashboardItem $item)
    {
        $item->delete();
        return back()->with('success', 'Item removed from dashboard');
    }

    public function reorder(Request $request, Dashboard $dashboard)
    {
        $request->validate(['items' => 'required|array']);

        foreach ($request->items as $index => $itemId) {
            DashboardItem::where('id', $itemId)
                ->where('dashboard_id', $dashboard->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
