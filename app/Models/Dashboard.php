<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Dashboard extends Model
{
    protected $fillable = ['uuid', 'name', 'description', 'settings', 'sort_order', 'is_active'];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Dashboard $dashboard) {
            $dashboard->uuid = $dashboard->uuid ?? Str::uuid()->toString();
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(DashboardItem::class)->orderBy('sort_order');
    }

    public function deviceGroups(): HasMany
    {
        return $this->hasMany(DeviceGroup::class)->orderBy('sort_order');
    }

    public function devices(): HasMany
    {
        return $this->items()->where('type', 'device');
    }

    public function flows(): HasMany
    {
        return $this->items()->where('type', 'flow');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
