<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class HomeySettings extends Model
{
    protected $fillable = ['name', 'ip_address', 'token', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected function token(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => decrypt($value),
            set: fn(string $value) => encrypt($value),
        );
    }

    public function getBaseUrlAttribute(): string
    {
        return "http://{$this->ip_address}/api";
    }
}
