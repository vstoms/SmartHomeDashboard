<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HomeyApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    public function __construct(protected HomeyApiService $homey) {}

    public function index(): JsonResponse
    {
        if (!$this->homey->isConfigured()) {
            return response()->json(['error' => 'Homey not configured'], 503);
        }

        return response()->json($this->homey->getDevices());
    }

    public function show(string $deviceId): JsonResponse
    {
        if (!$this->homey->isConfigured()) {
            return response()->json(['error' => 'Homey not configured'], 503);
        }

        $device = $this->homey->getDevice($deviceId);
        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        return response()->json($device);
    }

    public function control(Request $request, string $deviceId): JsonResponse
    {
        $validated = $request->validate([
            'capability' => 'required|string',
            'value' => 'required',
        ]);

        $success = $this->homey->setDeviceCapability(
            $deviceId,
            $validated['capability'],
            $validated['value']
        );

        return response()->json([
            'success' => $success,
            'device_id' => $deviceId,
            'capability' => $validated['capability'],
            'value' => $validated['value'],
        ]);
    }

    public function states(Request $request): JsonResponse
    {
        $deviceIds = $request->input('devices', []);
        $states = $this->homey->getDeviceStates($deviceIds);
        return response()->json($states);
    }
}
