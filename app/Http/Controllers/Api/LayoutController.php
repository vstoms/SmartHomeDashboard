<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\DashboardItem;
use App\Services\HomeyApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LayoutController extends Controller
{
    public function save(Request $request, string $uuid): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|numeric',
            'items.*.x' => 'required|numeric|min:0',
            'items.*.y' => 'required|numeric|min:0',
            'items.*.w' => 'required|numeric|min:1',
            'items.*.h' => 'required|numeric|min:1',
        ]);

        foreach ($validated['items'] as $itemData) {
            DashboardItem::where('id', (int) $itemData['id'])
                ->where('dashboard_id', $dashboard->id)
                ->update([
                    'grid_x' => (int) $itemData['x'],
                    'grid_y' => (int) $itemData['y'],
                    'grid_w' => (int) $itemData['w'],
                    'grid_h' => (int) $itemData['h'],
                ]);
        }

        return response()->json(['success' => true]);
    }

    public function availableItems(string $uuid, HomeyApiService $homey): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();

        $addedDeviceIds = $dashboard->items()->where('type', 'device')->pluck('homey_id')->toArray();
        $addedFlowIds = $dashboard->items()->where('type', 'flow')->pluck('homey_id')->toArray();

        $allDevices = $homey->getCachedDevices();
        $allFlows = $homey->getCachedFlows();

        $availableDevices = collect($allDevices)
            ->filter(fn($d, $id) => !in_array($id, $addedDeviceIds))
            ->map(fn($d, $id) => ['id' => $id, 'name' => $d['name'] ?? 'Unknown'])
            ->values();

        $availableFlows = collect($allFlows)
            ->filter(fn($f, $id) => !in_array($id, $addedFlowIds))
            ->map(fn($f, $id) => ['id' => $id, 'name' => $f['name'] ?? 'Unknown'])
            ->values();

        return response()->json([
            'devices' => $availableDevices,
            'flows' => $availableFlows,
        ]);
    }

    public function addItem(Request $request, string $uuid, HomeyApiService $homey): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'type' => 'required|in:device,flow',
            'homey_id' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        // Get device capabilities from Homey
        $capabilities = [];
        if ($validated['type'] === 'device') {
            $device = $homey->getDevice($validated['homey_id']);
            if ($device && isset($device['capabilitiesObj'])) {
                $capabilities = $device['capabilitiesObj'];
            }
        }

        // Find the next available grid position
        $maxY = $dashboard->items()->max('grid_y') ?? 0;
        $maxX = $dashboard->items()->where('grid_y', $maxY)->max('grid_x') ?? -1;

        $newX = $maxX + 1;
        $newY = $maxY;
        if ($newX >= 6) {
            $newX = 0;
            $newY = $maxY + 1;
        }

        $item = $dashboard->items()->create([
            'type' => $validated['type'],
            'homey_id' => $validated['homey_id'],
            'name' => $validated['name'],
            'capabilities' => $capabilities,
            'is_active' => true,
            'grid_x' => $newX,
            'grid_y' => $newY,
            'grid_w' => 1,
            'grid_h' => 1,
        ]);

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'type' => $item->type,
                'name' => $item->name,
                'homey_id' => $item->homey_id,
                'grid_x' => $item->grid_x,
                'grid_y' => $item->grid_y,
                'grid_w' => $item->grid_w,
                'grid_h' => $item->grid_h,
            ],
        ]);
    }

    public function removeItem(string $uuid, int $itemId): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();

        $item = DashboardItem::where('id', $itemId)
            ->where('dashboard_id', $dashboard->id)
            ->firstOrFail();

        $item->delete();

        return response()->json(['success' => true]);
    }

    public function getItem(string $uuid, int $itemId, HomeyApiService $homey): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();

        $item = DashboardItem::where('id', $itemId)
            ->where('dashboard_id', $dashboard->id)
            ->firstOrFail();

        // Get fresh capabilities from Homey for devices
        $capabilities = $item->capabilities ?? [];
        if ($item->type === 'device') {
            $device = $homey->getDevice($item->homey_id);
            if ($device && isset($device['capabilitiesObj'])) {
                $capabilities = $device['capabilitiesObj'];
                $item->update(['capabilities' => $capabilities]);
            }
        }

        return response()->json([
            'id' => $item->id,
            'type' => $item->type,
            'name' => $item->name,
            'homey_id' => $item->homey_id,
            'capabilities' => $capabilities,
            'settings' => $item->settings ?? [],
        ]);
    }

    public function updateItem(Request $request, string $uuid, int $itemId): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();

        $item = DashboardItem::where('id', $itemId)
            ->where('dashboard_id', $dashboard->id)
            ->firstOrFail();

        \Log::info('Update item request', ['itemId' => $itemId, 'input' => $request->all()]);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'display_capabilities' => 'nullable|array',
            'show_toggle' => 'nullable|boolean',
            'show_dimmer' => 'nullable|boolean',
            'show_thermostat' => 'nullable|boolean',
        ]);

        \Log::info('Validated data', $validated);

        $settings = $item->settings ?? [];

        if (isset($validated['display_capabilities'])) {
            $settings['display_capabilities'] = $validated['display_capabilities'];
        }
        if (isset($validated['show_toggle'])) {
            $settings['show_toggle'] = $validated['show_toggle'];
        }
        if (isset($validated['show_dimmer'])) {
            $settings['show_dimmer'] = $validated['show_dimmer'];
        }
        if (isset($validated['show_thermostat'])) {
            $settings['show_thermostat'] = $validated['show_thermostat'];
        }

        \Log::info('Settings to save', $settings);

        $item->update([
            'name' => $validated['name'] ?? $item->name,
            'settings' => $settings,
        ]);

        \Log::info('Item after save', ['settings' => $item->fresh()->settings]);

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'settings' => $settings,
            ],
        ]);
    }
}
