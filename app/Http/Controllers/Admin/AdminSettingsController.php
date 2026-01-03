<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeySettings;
use App\Services\HomeyApiService;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $settings = HomeySettings::first();
        $connectionStatus = null;

        if ($settings) {
            $homey = app(HomeyApiService::class);
            if ($homey->isConfigured()) {
                $devices = $homey->getDevices();
                $connectionStatus = [
                    'success' => !empty($devices) || is_array($devices),
                    'device_count' => count($devices),
                    'flow_count' => count($homey->getFlows()),
                ];
            }
        }

        return view('admin.settings.index', compact('settings', 'connectionStatus'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|max:255',
            'token' => 'required|string',
        ]);

        HomeySettings::updateOrCreate(
            ['id' => 1],
            [
                'name' => $validated['name'],
                'ip_address' => $validated['ip_address'],
                'token' => $validated['token'],
                'is_active' => true,
            ]
        );

        app()->forgetInstance(HomeyApiService::class);
        app(HomeyApiService::class)->clearCache();

        return back()->with('success', 'Homey settings saved successfully');
    }

    public function test()
    {
        $homey = app(HomeyApiService::class);

        if (!$homey->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Homey not configured']);
        }

        $devices = $homey->getDevices();
        $flows = $homey->getFlows();

        return response()->json([
            'success' => !empty($devices) || is_array($devices),
            'device_count' => count($devices),
            'flow_count' => count($flows),
        ]);
    }
}
