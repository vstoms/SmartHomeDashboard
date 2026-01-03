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

<!-- DEBUG: settings={{ json_encode($settings) }} displayCaps={{ json_encode($displayCaps) }} -->

<div class="device-card bg-gray-800 rounded-2xl p-4 flex flex-col overflow-hidden transition-all duration-200 hover:bg-gray-750 active:scale-95"
     data-device-id="{{ $item->homey_id }}"
     data-item-id="{{ $item->id }}"
     data-display-capabilities="{{ json_encode($displayCaps) }}">

    <div class="flex justify-between items-start gap-2 flex-shrink-0">
        <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-base truncate">{{ $item->name }}</h3>
            <span class="device-status text-sm text-gray-400">--</span>
        </div>

        @if($hasOnoff && $showToggle)
            <!-- On/Off Toggle -->
            <button class="toggle-btn w-14 h-8 rounded-full bg-gray-600 relative transition-colors flex-shrink-0 touch-manipulation"
                    data-capability="onoff"
                    aria-label="Toggle device">
                <span class="toggle-indicator absolute top-1 left-1 w-6 h-6 bg-white rounded-full transition-transform duration-200 shadow-md"></span>
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
                    <div class="sensor-item bg-gray-700/50 rounded-lg px-3 py-2" data-sensor="{{ $capId }}">
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
        <!-- Dimmer Slider -->
        <div class="dimmer-control mt-auto pt-2 flex-shrink-0">
            <div class="flex items-center gap-3">
                <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/>
                </svg>
                <input type="range" min="0" max="100" value="100"
                       class="flex-1 h-2 bg-gray-600 rounded-lg appearance-none cursor-pointer accent-yellow-400"
                       data-capability="dim">
                <span class="dimmer-value text-sm text-gray-400 w-10 text-right">100%</span>
            </div>
        </div>
    @endif

    @if($hasThermostat && $showThermostat)
        <!-- Thermostat Controls -->
        <div class="thermostat-control mt-auto pt-2 flex-shrink-0">
            <div class="flex items-center justify-between">
                <button class="temp-down text-xl w-10 h-10 bg-gray-700 hover:bg-gray-600 rounded-full flex items-center justify-center touch-manipulation active:scale-95 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                </button>
                <div class="text-center">
                    <span class="temp-value text-2xl font-bold">--</span>
                    <span class="text-gray-400">°C</span>
                </div>
                <button class="temp-up text-xl w-10 h-10 bg-gray-700 hover:bg-gray-600 rounded-full flex items-center justify-center touch-manipulation active:scale-95 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif
</div>
