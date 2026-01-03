<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeySettings;
use App\Services\HomeyApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $settings = HomeySettings::first();
        $connectionStatus = null;

        if ($settings) {
            $homey = app(HomeyApiService::class);
            if ($homey->isConfigured()) {
                try {
                    $devices = $homey->getDevices();
                    $flows = $homey->getFlows();
                    $deviceCount = is_array($devices) ? count($devices) : 0;
                    $flowCount = is_array($flows) ? count($flows) : 0;

                    $connectionStatus = [
                        'success' => $deviceCount > 0 || $flowCount > 0,
                        'device_count' => $deviceCount,
                        'flow_count' => $flowCount,
                    ];
                } catch (\Exception $e) {
                    $connectionStatus = [
                        'success' => false,
                        'device_count' => 0,
                        'flow_count' => 0,
                    ];
                }
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

        $data = [
            'name' => $validated['name'],
            'ip_address' => $validated['ip_address'],
            'is_active' => true,
        ];

        // Only update token if it's not the masked placeholder
        if ($validated['token'] !== '••••••••••••••••') {
            $data['token'] = $validated['token'];
        }

        $existing = HomeySettings::first();
        if ($existing) {
            // If token is masked and no existing token, require new token
            if ($validated['token'] === '••••••••••••••••' && !$existing->token) {
                return back()->withErrors(['token' => 'Please enter your API token']);
            }
            $existing->update($data);
        } else {
            // New record - token is required
            if ($validated['token'] === '••••••••••••••••') {
                return back()->withErrors(['token' => 'Please enter your API token']);
            }
            $data['token'] = $validated['token'];
            HomeySettings::create($data);
        }

        app()->forgetInstance(HomeyApiService::class);
        app(HomeyApiService::class)->clearCache();

        return back()->with('success', 'Homey settings saved successfully');
    }

    public function test(Request $request)
    {
        $ipAddress = $request->input('ip_address');
        $token = $request->input('token');
        $savedSettings = HomeySettings::first();

        // Use form IP, but if token is masked, use saved token
        if ($token === '••••••••••••••••' && $savedSettings) {
            $token = $savedSettings->token;
        }

        // Test with provided/resolved values
        if ($ipAddress && $token) {
            $baseUrl = "http://{$ipAddress}/api";

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(5)->connectTimeout(3)->get("{$baseUrl}/manager/devices/device");

                if ($response->successful()) {
                    $devices = $response->json() ?? [];
                    $flowResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                    ])->timeout(5)->connectTimeout(3)->get("{$baseUrl}/manager/flow/flow");
                    $flows = $flowResponse->successful() ? ($flowResponse->json() ?? []) : [];

                    $deviceCount = is_array($devices) ? count($devices) : 0;
                    $flowCount = is_array($flows) ? count($flows) : 0;

                    return response()->json([
                        'success' => true,
                        'device_count' => $deviceCount,
                        'flow_count' => $flowCount,
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'HTTP ' . $response->status() . ' - check IP and token',
                ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                return response()->json(['success' => false, 'message' => 'Connection timeout - check IP address']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        }

        return response()->json(['success' => false, 'message' => 'Please enter IP address and token']);
    }
}
