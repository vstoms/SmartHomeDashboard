<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\DeviceGroup;
use App\Services\HomeyApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceGroupController extends Controller
{
    /**
     * List all device groups for a dashboard
     */
    public function index(string $uuid): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();
        
        $groups = $dashboard->deviceGroups()->get()->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'device_ids' => $group->device_ids ?? [],
                'device_count' => $group->getDeviceCount(),
                'grid_x' => $group->grid_x,
                'grid_y' => $group->grid_y,
                'grid_w' => $group->grid_w,
                'grid_h' => $group->grid_h,
                'settings' => $group->settings ?? [],
            ];
        });
        
        return response()->json(['groups' => $groups]);
    }

    /**
     * Create a new device group
     */
    public function store(Request $request, string $uuid, HomeyApiService $homey): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_ids' => 'nullable|array',
            'device_ids.*' => 'string',
        ]);
        
        // Find the next available grid position
        $maxY = max(
            $dashboard->items()->max('grid_y') ?? 0,
            $dashboard->deviceGroups()->max('grid_y') ?? 0
        );
        $maxX = max(
            $dashboard->items()->where('grid_y', $maxY)->max('grid_x') ?? -1,
            $dashboard->deviceGroups()->where('grid_y', $maxY)->max('grid_x') ?? -1
        );
        
        $newX = $maxX + 2; // Groups are typically 2 wide
        $newY = $maxY;
        if ($newX >= 6) {
            $newX = 0;
            $newY = $maxY + 2; // Groups are typically 2 tall
        }
        
        $group = $dashboard->deviceGroups()->create([
            'name' => $validated['name'],
            'device_ids' => $validated['device_ids'] ?? [],
            'grid_x' => $newX,
            'grid_y' => $newY,
            'grid_w' => 2,
            'grid_h' => 2,
            'is_active' => true,
        ]);
        
        // Get device details for the response
        $devices = $this->getDevicesForGroup($group, $homey);
        
        return response()->json([
            'success' => true,
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'device_ids' => $group->device_ids ?? [],
                'devices' => $devices,
                'grid_x' => $group->grid_x,
                'grid_y' => $group->grid_y,
                'grid_w' => $group->grid_w,
                'grid_h' => $group->grid_h,
            ],
        ]);
    }

    /**
     * Get a single device group
     */
    public function show(string $uuid, int $groupId, HomeyApiService $homey): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();
        
        $group = DeviceGroup::where('id', $groupId)
            ->where('dashboard_id', $dashboard->id)
            ->firstOrFail();
        
        $devices = $this->getDevicesForGroup($group, $homey);
        
        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'device_ids' => $group->device_ids ?? [],
            'devices' => $devices,
            'grid_x' => $group->grid_x,
            'grid_y' => $group->grid_y,
            'grid_w' => $group->grid_w,
            'grid_h' => $group->grid_h,
            'settings' => $group->settings ?? [],
        ]);
    }

    /**
     * Update a device group
     */
    public function update(Request $request, string $uuid, int $groupId): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();
        
        $group = DeviceGroup::where('id', $groupId)
            ->where('dashboard_id', $dashboard->id)
            ->firstOrFail();
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'device_ids' => 'sometimes|array',
            'device_ids.*' => 'string',
            'settings' => 'sometimes|array',
        ]);
        
        $group->update($validated);
        
        return response()->json([
            'success' => true,
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'device_ids' => $group->device_ids ?? [],
                'settings' => $group->settings ?? [],
            ],
        ]);
    }

    /**
     * Delete a device group
     */
    public function destroy(string $uuid, int $groupId): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();
        
        $group = DeviceGroup::where('id', $groupId)
            ->where('dashboard_id', $dashboard->id)
            ->firstOrFail();
        
        $group->delete();
        
        return response()->json(['success' => true]);
    }

    /**
     * Add a device to a group
     */
    public function addDevice(Request $request, string $uuid, int $groupId): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();
        
        $group = DeviceGroup::where('id', $groupId)
            ->where('dashboard_id', $dashboard->id)
            ->firstOrFail();
        
        $validated = $request->validate([
            'device_id' => 'required|string',
        ]);
        
        $group->addDevice($validated['device_id']);
        
        return response()->json([
            'success' => true,
            'device_ids' => $group->device_ids,
        ]);
    }

    /**
     * Remove a device from a group
     */
    public function removeDevice(Request $request, string $uuid, int $groupId): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();
        
        $group = DeviceGroup::where('id', $groupId)
            ->where('dashboard_id', $dashboard->id)
            ->firstOrFail();
        
        $validated = $request->validate([
            'device_id' => 'required|string',
        ]);
        
        $group->removeDevice($validated['device_id']);
        
        return response()->json([
            'success' => true,
            'device_ids' => $group->device_ids,
        ]);
    }

    /**
     * Update grid position for a group
     */
    public function updatePosition(Request $request, string $uuid, int $groupId): JsonResponse
    {
        $dashboard = Dashboard::where('uuid', $uuid)->firstOrFail();
        
        $group = DeviceGroup::where('id', $groupId)
            ->where('dashboard_id', $dashboard->id)
            ->firstOrFail();
        
        $validated = $request->validate([
            'x' => 'required|numeric|min:0',
            'y' => 'required|numeric|min:0',
            'w' => 'required|numeric|min:1',
            'h' => 'required|numeric|min:1',
        ]);
        
        $group->update([
            'grid_x' => (int) $validated['x'],
            'grid_y' => (int) $validated['y'],
            'grid_w' => (int) $validated['w'],
            'grid_h' => (int) $validated['h'],
        ]);
        
        return response()->json(['success' => true]);
    }

    /**
     * Get device details for a group
     */
    private function getDevicesForGroup(DeviceGroup $group, HomeyApiService $homey): array
    {
        $deviceIds = $group->getDeviceIdsArray();
        $devices = [];
        
        foreach ($deviceIds as $deviceId) {
            $device = $homey->getDevice($deviceId);
            if ($device) {
                $capabilities = $device['capabilitiesObj'] ?? [];
                $hasDim = isset($capabilities['dim']);
                $hasOnoff = isset($capabilities['onoff']);
                
                $devices[] = [
                    'id' => $deviceId,
                    'name' => $device['name'] ?? 'Unknown',
                    'type' => $hasDim ? 'dimmer' : 'switch',
                    'value' => $hasDim ? round(($capabilities['dim']['value'] ?? 0) * 100) : 0,
                    'on' => $hasOnoff ? ($capabilities['onoff']['value'] ?? false) : false,
                ];
            }
        }
        
        return $devices;
    }
}
