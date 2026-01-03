<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardItem extends Model
{
    protected $fillable = [
        'dashboard_id',
        'type',
        'homey_id',
        'name',
        'icon',
        'capabilities',
        'settings',
        'sort_order',
        'is_active',
        'grid_x',
        'grid_y',
        'grid_w',
        'grid_h',
    ];

    protected $casts = [
        'capabilities' => 'array',
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

    public function isDevice(): bool
    {
        return $this->type === 'device';
    }

    public function isFlow(): bool
    {
        return $this->type === 'flow';
    }
}
