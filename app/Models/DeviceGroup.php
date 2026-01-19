<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceGroup extends Model
{
    protected $fillable = [
        'dashboard_id',
        'name',
        'device_ids',
        'settings',
        'grid_x',
        'grid_y',
        'grid_w',
        'grid_h',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'device_ids' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'grid_x' => 'integer',
        'grid_y' => 'integer',
        'grid_w' => 'integer',
        'grid_h' => 'integer',
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * Get the device IDs as an array
     */
    public function getDeviceIdsArray(): array
    {
        return $this->device_ids ?? [];
    }

    /**
     * Check if a device is in this group
     */
    public function hasDevice(string $deviceId): bool
    {
        return in_array($deviceId, $this->getDeviceIdsArray());
    }

    /**
     * Add a device to the group
     */
    public function addDevice(string $deviceId): void
    {
        $ids = $this->getDeviceIdsArray();
        if (!in_array($deviceId, $ids)) {
            $ids[] = $deviceId;
            $this->device_ids = $ids;
            $this->save();
        }
    }

    /**
     * Remove a device from the group
     */
    public function removeDevice(string $deviceId): void
    {
        $ids = $this->getDeviceIdsArray();
        $ids = array_values(array_filter($ids, fn($id) => $id !== $deviceId));
        $this->device_ids = $ids;
        $this->save();
    }

    /**
     * Get the count of devices in this group
     */
    public function getDeviceCount(): int
    {
        return count($this->getDeviceIdsArray());
    }
}
