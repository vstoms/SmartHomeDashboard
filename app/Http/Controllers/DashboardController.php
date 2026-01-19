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

        $dashboard->load([
            'items' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            },
            'deviceGroups' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }
        ]);

        // Get device details for each group
        $groupsWithDevices = $dashboard->deviceGroups->map(function ($group) use ($homey) {
            $devices = [];
            foreach ($group->getDeviceIdsArray() as $deviceId) {
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
            
            return [
                'id' => $group->id,
                'name' => $group->name,
                'devices' => $devices,
                'grid_x' => $group->grid_x,
                'grid_y' => $group->grid_y,
                'grid_w' => $group->grid_w,
                'grid_h' => $group->grid_h,
            ];
        });

        return view('dashboard.show', compact('dashboard', 'groupsWithDevices'));
    }
}
