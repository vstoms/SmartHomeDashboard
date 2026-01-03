@extends('layouts.admin')
@section('title', 'Configure ' . $item->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.dashboards.edit', $item->dashboard) }}" class="text-blue-600 hover:underline">
        &larr; Back to Dashboard
    </a>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">{{ $item->name }}</h2>
        <p class="text-gray-500 text-sm mb-6">Configure which information to display on the dashboard card.</p>

        <form action="{{ route('admin.items.update', $item) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Display Name</label>
                <input type="text" name="name" value="{{ $item->name }}"
                       class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
            </div>

            @if($item->isDevice() && !empty($capabilities))
                <div class="mb-6">
                    <h3 class="text-sm font-medium mb-3">Display Options</h3>

                    <!-- Control Capabilities -->
                    @php
                        $controlCaps = ['onoff', 'dim', 'target_temperature'];
                        $displayCaps = collect($capabilities)->filter(fn($cap, $key) => !in_array($key, $controlCaps));
                        $currentSettings = $item->settings ?? [];
                        $displaySettings = $currentSettings['display_capabilities'] ?? [];
                    @endphp

                    @if($displayCaps->isNotEmpty())
                        <div class="space-y-2 border rounded-lg p-4 bg-gray-50">
                            <p class="text-xs text-gray-500 mb-2">Select which sensor values and information to display:</p>
                            @foreach($displayCaps as $capId => $cap)
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-100 rounded cursor-pointer">
                                    <input type="checkbox" name="display_capabilities[]" value="{{ $capId }}"
                                           {{ in_array($capId, $displaySettings) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600">
                                    <div class="flex-1">
                                        <span class="font-medium text-sm">{{ $cap['title'] ?? ucfirst(str_replace('_', ' ', $capId)) }}</span>
                                        @if(isset($cap['value']))
                                            <span class="text-gray-500 text-sm ml-2">
                                                (Current: {{ is_bool($cap['value']) ? ($cap['value'] ? 'Yes' : 'No') : $cap['value'] }}{{ $cap['units'] ?? '' }})
                                            </span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">This device has no additional sensor data to display.</p>
                    @endif
                </div>

                <!-- Control Options -->
                @php
                    $hasOnoff = isset($capabilities['onoff']);
                    $hasDim = isset($capabilities['dim']);
                    $hasThermostat = isset($capabilities['target_temperature']);
                    $showToggle = $currentSettings['show_toggle'] ?? true;
                    $showDimmer = $currentSettings['show_dimmer'] ?? true;
                    $showThermostat = $currentSettings['show_thermostat'] ?? true;
                @endphp

                @if($hasOnoff || $hasDim || $hasThermostat)
                    <div class="mb-6">
                        <h3 class="text-sm font-medium mb-3">Control Options</h3>
                        <div class="space-y-2 border rounded-lg p-4 bg-gray-50">
                            @if($hasOnoff)
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-100 rounded cursor-pointer">
                                    <input type="checkbox" name="show_toggle" value="1" {{ $showToggle ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600">
                                    <span class="font-medium text-sm">Show On/Off Toggle</span>
                                </label>
                            @endif
                            @if($hasDim)
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-100 rounded cursor-pointer">
                                    <input type="checkbox" name="show_dimmer" value="1" {{ $showDimmer ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600">
                                    <span class="font-medium text-sm">Show Dimmer Slider</span>
                                </label>
                            @endif
                            @if($hasThermostat)
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-100 rounded cursor-pointer">
                                    <input type="checkbox" name="show_thermostat" value="1" {{ $showThermostat ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600">
                                    <span class="font-medium text-sm">Show Thermostat Controls</span>
                                </label>
                            @endif
                        </div>
                    </div>
                @endif
            @endif

            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Save Settings
                </button>
                <a href="{{ route('admin.dashboards.edit', $item->dashboard) }}"
                   class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
