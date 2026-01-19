@props(['title' => 'Room', 'devices' => []])

{{--
Multi-Switch Card Component
Displays multiple toggle switches within a single card, similar to Home Assistant style.

Usage:
<x-multi-switch-card 
    title="Living Room" 
    :devices="[
        ['id' => 'device1', 'name' => 'Ceiling Light', 'type' => 'dimmer', 'value' => 75, 'on' => true],
        ['id' => 'device2', 'name' => 'Floor Lamp', 'type' => 'switch', 'on' => false],
        ['id' => 'device3', 'name' => 'Accent Light', 'type' => 'dimmer', 'value' => 34, 'on' => true],
    ]"
/>

Device types:
- 'switch': Simple on/off toggle
- 'dimmer': Slider with percentage value
--}}

<div class="multi-switch-card lux-card rounded-2xl p-5 flex flex-col overflow-hidden transition-all duration-300"
     data-card-type="multi-switch">
    
    {{-- Card Title --}}
    <h3 class="text-lg font-semibold text-white mb-4">{{ $title }}</h3>
    
    {{-- Device List --}}
    <div class="multi-switch-list flex flex-col gap-3">
        @forelse($devices as $device)
            @php
                $deviceId = $device['id'] ?? 'device-' . $loop->index;
                $deviceName = $device['name'] ?? 'Device ' . ($loop->index + 1);
                $deviceType = $device['type'] ?? 'switch';
                $isOn = $device['on'] ?? false;
                $value = $device['value'] ?? ($isOn ? 100 : 0);
            @endphp
            
            <div class="multi-switch-row flex items-center gap-3"
                 data-device-id="{{ $deviceId }}"
                 data-device-type="{{ $deviceType }}">
                
                {{-- Device Name --}}
                <span class="multi-switch-name flex-shrink-0 text-sm font-medium text-gray-200 w-32 truncate">
                    {{ $deviceName }}
                </span>
                
                {{-- Slider/Toggle Control --}}
                <div class="multi-switch-control flex-1 flex items-center">
                    @if($deviceType === 'dimmer')
                        {{-- Dimmer Slider with Value --}}
                        <div class="multi-switch-slider-container relative flex-1 flex items-center">
                            <div class="multi-switch-slider-track relative w-full h-10 rounded-full overflow-hidden"
                                 data-value="{{ $value }}">
                                {{-- Track Background --}}
                                <div class="absolute inset-0 bg-slate-800/80 rounded-full"></div>
                                
                                {{-- Filled Track --}}
                                <div class="multi-switch-slider-fill absolute inset-y-0 left-0 rounded-full transition-all duration-200"
                                     style="width: {{ $value }}%;">
                                </div>
                                
                                {{-- Slider Thumb --}}
                                <div class="multi-switch-slider-thumb absolute top-1 bottom-1 w-8 rounded-full transition-all duration-200 {{ $isOn ? 'is-on' : '' }}"
                                     style="left: calc({{ max(0, min($value - 5, 95)) }}% - 0px);">
                                </div>
                                
                                {{-- Value Display --}}
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <span class="multi-switch-value text-sm font-semibold text-gray-300">{{ $value }}%</span>
                                </div>
                                
                                {{-- Hidden Range Input --}}
                                <input type="range" 
                                       min="0" 
                                       max="100" 
                                       value="{{ $value }}"
                                       class="multi-switch-range absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       data-device-id="{{ $deviceId }}">
                            </div>
                        </div>
                    @else
                        {{-- Simple Toggle Switch --}}
                        <div class="multi-switch-toggle-container flex-1 flex items-center justify-center">
                            <button class="multi-switch-toggle w-16 h-9 rounded-full relative transition-all duration-300 {{ $isOn ? 'is-on' : '' }}"
                                    data-device-id="{{ $deviceId }}"
                                    aria-label="Toggle {{ $deviceName }}"
                                    aria-pressed="{{ $isOn ? 'true' : 'false' }}">
                                <span class="multi-switch-toggle-indicator absolute top-1 left-1 w-7 h-7 rounded-full transition-transform duration-200"></span>
                            </button>
                        </div>
                    @endif
                </div>
                
                {{-- Power Button --}}
                <button class="multi-switch-power-btn w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 transition-all duration-300 {{ $isOn ? 'is-on' : '' }}"
                        data-device-id="{{ $deviceId }}"
                        aria-label="Power {{ $deviceName }}"
                        aria-pressed="{{ $isOn ? 'true' : 'false' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/>
                    </svg>
                </button>
            </div>
        @empty
            <p class="text-gray-500 text-sm text-center py-4">No devices configured</p>
        @endforelse
    </div>
    
    {{-- Optional: Add Device Button (for edit mode) --}}
    <div class="multi-switch-add-row hidden mt-3 pt-3 border-t border-white/10">
        <button class="multi-switch-add-btn w-full py-2 text-sm text-gray-400 hover:text-white hover:bg-white/5 rounded-lg transition-colors flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Device
        </button>
    </div>
</div>
