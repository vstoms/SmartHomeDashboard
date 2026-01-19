@props(['item'])

@php
    $settings = $item->settings ?? [];
    $displayCaps = $settings['display_capabilities'] ?? [];
    $showToggle = $settings['show_toggle'] ?? true;
    $showDimmer = $settings['show_dimmer'] ?? true;
    $showThermostat = $settings['show_thermostat'] ?? true;
    $capabilities = $item->capabilities ?? [];
    $hasOnoff = isset($capabilities['onoff']);
    $hasDim = isset($capabilities['dim']);
    $hasThermostat = isset($capabilities['target_temperature']);
@endphp

<div class="device-card lux-card rounded-2xl p-4 flex flex-col overflow-hidden transition-all duration-300 active:scale-95"
     data-device-id="{{ $item->homey_id }}"
     data-item-id="{{ $item->id }}"
     data-display-capabilities="{{ json_encode($displayCaps) }}">

    <div class="flex justify-between items-start gap-2 flex-shrink-0">
        <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-base truncate">{{ $item->name }}</h3>
            <div class="device-status-row text-sm text-gray-300">
                <span class="status-dot" aria-hidden="true"></span>
                <span class="device-status">--</span>
            </div>
        </div>

        @if($hasOnoff && $showToggle)
            <!-- On/Off Toggle -->
            <button class="toggle-btn lux-toggle w-16 h-9 rounded-full relative flex-shrink-0 touch-manipulation"
                    data-capability="onoff"
                    aria-label="Toggle device"
                    aria-pressed="false">
                <span class="toggle-indicator absolute top-1 left-1 w-7 h-7 rounded-full transition-transform duration-200"></span>
            </button>
        @endif
    </div>

    <!-- Sensor Values Display -->
    @if(count($displayCaps) > 0)
        <div class="sensor-values mt-2 flex-1 overflow-y-auto min-h-0 -mx-1 px-1">
            <div class="grid grid-cols-2 gap-2 pb-2">
                @foreach($displayCaps as $capId)
                    @php
                        $cap = $capabilities[$capId] ?? null;
                        $title = $cap['title'] ?? ucfirst(str_replace(['measure_', 'meter_', 'alarm_', '_'], ['', '', '', ' '], $capId));
                        $units = $cap['units'] ?? '';
                    @endphp
                    <div class="sensor-item lux-sensor rounded-lg px-3 py-2" data-sensor="{{ $capId }}">
                        <div class="text-xs text-gray-400 truncate">{{ $title }}</div>
                        <div class="sensor-value text-sm font-medium leading-tight" data-capability="{{ $capId }}">--{{ $units }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="flex-1"></div>
    @endif

    @if($hasDim && $showDimmer)
        <!-- Dimmer Controls -->
        <div class="dimmer-control mt-auto pt-2 flex-shrink-0">
            <div class="flex items-center gap-4">
                <div class="lux-dimmer" style="--dimmer: 100;">
                    <div class="lux-dimmer-center">
                        <span class="dimmer-value text-sm font-semibold">100%</span>
                        <span class="dimmer-label text-[10px] uppercase tracking-[0.2em] text-gray-400">Dim</span>
                    </div>
                </div>
                <input type="range" min="0" max="100" value="100"
                       class="lux-slider flex-1"
                       data-capability="dim">
            </div>
        </div>
    @endif

    @if($hasThermostat && $showThermostat)
        <!-- Thermostat Controls -->
        <div class="thermostat-control mt-auto pt-2 flex-shrink-0">
            <div class="flex items-center justify-between">
                <button class="temp-down lux-circle-btn text-xl w-11 h-11 rounded-full flex items-center justify-center touch-manipulation active:scale-95 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                </button>
                <div class="text-center">
                    <span class="temp-value text-2xl font-bold">--</span>
                    <span class="text-gray-400">°C</span>
                </div>
                <button class="temp-up lux-circle-btn text-xl w-11 h-11 rounded-full flex items-center justify-center touch-manipulation active:scale-95 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif
</div>
