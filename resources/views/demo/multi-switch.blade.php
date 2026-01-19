@extends('layouts.dashboard')
@section('title', 'Multi-Switch Card Demo')

@section('content')
<div class="p-4 md:p-6 max-w-7xl mx-auto">
    <header class="mb-8">
        <h1 class="text-2xl md:text-3xl font-bold">Multi-Switch Card Demo</h1>
        <p class="text-gray-400 mt-2">Home Assistant style card with multiple toggle switches</p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        {{-- Example 1: Living Room with Dimmers --}}
        <x-multi-switch-card 
            title="Stue" 
            :devices="[
                ['id' => 'lamp-tv', 'name' => 'Lampe TV-Stue', 'type' => 'dimmer', 'value' => 34, 'on' => true],
                ['id' => 'spotter-salong', 'name' => 'Spotter Salong', 'type' => 'dimmer', 'value' => 18, 'on' => true],
                ['id' => 'spotter-spisestue', 'name' => 'Spotter Spisestue', 'type' => 'dimmer', 'value' => 6, 'on' => true],
            ]"
        />

        {{-- Example 2: Bedroom with Mixed Controls --}}
        <x-multi-switch-card 
            title="Bedroom" 
            :devices="[
                ['id' => 'ceiling-light', 'name' => 'Ceiling Light', 'type' => 'dimmer', 'value' => 75, 'on' => true],
                ['id' => 'bedside-lamp-l', 'name' => 'Bedside Lamp L', 'type' => 'switch', 'on' => true],
                ['id' => 'bedside-lamp-r', 'name' => 'Bedside Lamp R', 'type' => 'switch', 'on' => false],
                ['id' => 'accent-light', 'name' => 'Accent Light', 'type' => 'dimmer', 'value' => 45, 'on' => true],
            ]"
        />

        {{-- Example 3: Kitchen with Simple Switches --}}
        <x-multi-switch-card 
            title="Kitchen" 
            :devices="[
                ['id' => 'main-light', 'name' => 'Main Light', 'type' => 'switch', 'on' => true],
                ['id' => 'under-cabinet', 'name' => 'Under Cabinet', 'type' => 'switch', 'on' => true],
                ['id' => 'pendant-lights', 'name' => 'Pendant Lights', 'type' => 'switch', 'on' => false],
            ]"
        />

        {{-- Example 4: Office with 2 Devices --}}
        <x-multi-switch-card 
            title="Office" 
            :devices="[
                ['id' => 'desk-lamp', 'name' => 'Desk Lamp', 'type' => 'dimmer', 'value' => 100, 'on' => true],
                ['id' => 'monitor-light', 'name' => 'Monitor Light', 'type' => 'dimmer', 'value' => 50, 'on' => true],
            ]"
        />

        {{-- Example 5: Bathroom with Many Devices --}}
        <x-multi-switch-card 
            title="Bathroom" 
            :devices="[
                ['id' => 'vanity-light', 'name' => 'Vanity Light', 'type' => 'dimmer', 'value' => 80, 'on' => true],
                ['id' => 'shower-light', 'name' => 'Shower Light', 'type' => 'switch', 'on' => false],
                ['id' => 'mirror-light', 'name' => 'Mirror Light', 'type' => 'dimmer', 'value' => 60, 'on' => true],
                ['id' => 'exhaust-fan', 'name' => 'Exhaust Fan', 'type' => 'switch', 'on' => false],
                ['id' => 'night-light', 'name' => 'Night Light', 'type' => 'dimmer', 'value' => 10, 'on' => true],
            ]"
        />

        {{-- Example 6: Outdoor with All Off --}}
        <x-multi-switch-card 
            title="Outdoor" 
            :devices="[
                ['id' => 'porch-light', 'name' => 'Porch Light', 'type' => 'switch', 'on' => false],
                ['id' => 'garden-lights', 'name' => 'Garden Lights', 'type' => 'dimmer', 'value' => 0, 'on' => false],
                ['id' => 'pathway-lights', 'name' => 'Pathway Lights', 'type' => 'switch', 'on' => false],
                ['id' => 'garage-light', 'name' => 'Garage Light', 'type' => 'switch', 'on' => false],
            ]"
        />

        {{-- Example 7: Large Room with 10+ Devices --}}
        <div class="md:col-span-2">
            <x-multi-switch-card 
                title="Entertainment Room" 
                :devices="[
                    ['id' => 'main-ceiling', 'name' => 'Main Ceiling', 'type' => 'dimmer', 'value' => 40, 'on' => true],
                    ['id' => 'tv-backlight', 'name' => 'TV Backlight', 'type' => 'dimmer', 'value' => 25, 'on' => true],
                    ['id' => 'floor-lamp-1', 'name' => 'Floor Lamp 1', 'type' => 'dimmer', 'value' => 60, 'on' => true],
                    ['id' => 'floor-lamp-2', 'name' => 'Floor Lamp 2', 'type' => 'dimmer', 'value' => 60, 'on' => true],
                    ['id' => 'wall-sconce-l', 'name' => 'Wall Sconce L', 'type' => 'switch', 'on' => true],
                    ['id' => 'wall-sconce-r', 'name' => 'Wall Sconce R', 'type' => 'switch', 'on' => true],
                    ['id' => 'bar-lights', 'name' => 'Bar Lights', 'type' => 'dimmer', 'value' => 80, 'on' => true],
                    ['id' => 'shelf-lights', 'name' => 'Shelf Lights', 'type' => 'dimmer', 'value' => 30, 'on' => true],
                    ['id' => 'gaming-lights', 'name' => 'Gaming Lights', 'type' => 'dimmer', 'value' => 100, 'on' => true],
                    ['id' => 'ambient-strip', 'name' => 'Ambient Strip', 'type' => 'dimmer', 'value' => 50, 'on' => true],
                    ['id' => 'projector-light', 'name' => 'Projector Light', 'type' => 'switch', 'on' => false],
                    ['id' => 'exit-sign', 'name' => 'Exit Sign', 'type' => 'switch', 'on' => true],
                ]"
            />
        </div>

        {{-- Example 8: Empty Card --}}
        <x-multi-switch-card 
            title="Empty Room" 
            :devices="[]"
        />

    </div>

    {{-- Usage Documentation --}}
    <div class="mt-12 lux-panel rounded-2xl p-6">
        <h2 class="text-xl font-semibold mb-4">Usage</h2>
        <pre class="bg-black/30 rounded-lg p-4 overflow-x-auto text-sm text-gray-300"><code>&lt;x-multi-switch-card 
    title="Room Name" 
    :devices="[
        ['id' => 'device-1', 'name' => 'Device Name', 'type' => 'dimmer', 'value' => 75, 'on' => true],
        ['id' => 'device-2', 'name' => 'Another Device', 'type' => 'switch', 'on' => false],
    ]"
/&gt;</code></pre>
        
        <h3 class="text-lg font-semibold mt-6 mb-3">Device Properties</h3>
        <ul class="list-disc list-inside text-gray-400 space-y-2">
            <li><code class="text-amber-400">id</code> - Unique device identifier</li>
            <li><code class="text-amber-400">name</code> - Display name for the device</li>
            <li><code class="text-amber-400">type</code> - Either 'dimmer' (slider) or 'switch' (toggle)</li>
            <li><code class="text-amber-400">value</code> - Percentage value for dimmers (0-100)</li>
            <li><code class="text-amber-400">on</code> - Boolean for power state</li>
        </ul>
    </div>
</div>
@endsection
