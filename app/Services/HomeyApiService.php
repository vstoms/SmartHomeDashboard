<?php

namespace App\Services;

use App\Models\HomeySettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class HomeyApiService
{
    protected ?HomeySettings $settings;
    protected string $baseUrl;

    public function __construct()
    {
        $this->settings = HomeySettings::where('is_active', true)->first();
        $this->baseUrl = $this->settings?->base_url ?? '';
    }

    public function isConfigured(): bool
    {
        return $this->settings !== null;
    }

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->settings->token,
            'Content-Type' => 'application/json',
        ])->timeout(10)->baseUrl($this->baseUrl);
    }

    // === DEVICES ===

    public function getDevices(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->request()->get('/manager/devices/device');
            return $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getDevice(string $deviceId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->request()->get("/manager/devices/device/{$deviceId}");
            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setDeviceCapability(string $deviceId, string $capability, mixed $value): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = $this->request()->put(
                "/manager/devices/device/{$deviceId}/capability/{$capability}",
                ['value' => $value]
            );
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getDeviceStates(array $deviceIds): array
    {
        $states = [];
        foreach ($deviceIds as $deviceId) {
            $device = $this->getDevice($deviceId);
            if ($device) {
                $states[$deviceId] = $device['capabilitiesObj'] ?? [];
            }
        }
        return $states;
    }

    // === FLOWS ===

    public function getFlows(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->request()->get('/manager/flow/flow');
            return $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function triggerFlow(string $flowId): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = $this->request()->post("/manager/flow/flow/{$flowId}/trigger");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    // === CACHED VERSIONS FOR ADMIN ===

    public function getCachedDevices(int $ttl = 60): array
    {
        return Cache::remember('homey_devices', $ttl, fn() => $this->getDevices());
    }

    public function getCachedFlows(int $ttl = 60): array
    {
        return Cache::remember('homey_flows', $ttl, fn() => $this->getFlows());
    }

    public function clearCache(): void
    {
        Cache::forget('homey_devices');
        Cache::forget('homey_flows');
    }
}
