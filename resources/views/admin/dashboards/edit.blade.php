@extends('layouts.admin')
@section('title', 'Edit Dashboard')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.dashboards.index') }}" class="text-blue-600 hover:underline">
        &larr; Back to Dashboards
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Dashboard Settings -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Dashboard Settings</h2>
            <form action="{{ route('admin.dashboards.update', $dashboard) }}" method="POST">
                @csrf @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input type="text" name="name" value="{{ $dashboard->name }}"
                               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Description</label>
                        <textarea name="description" rows="2"
                                  class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">{{ $dashboard->description }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Public URL</label>
                        <div class="flex gap-2">
                            <input type="text" readonly id="dashboard-url"
                                   value="{{ route('dashboard.show', $dashboard) }}"
                                   class="flex-1 border rounded-lg px-3 py-2 bg-gray-50 font-mono text-sm">
                            <button type="button" onclick="copyUrl()"
                                    class="px-3 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 text-sm">
                                Copy
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               {{ $dashboard->is_active ? 'checked' : '' }}
                               class="rounded border-gray-300">
                        <label for="is_active" class="text-sm">Active (visible to users)</label>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Current Items -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Dashboard Items</h2>
            <div id="dashboard-items" class="space-y-2">
                @forelse($dashboard->items as $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <span class="text-xs px-2 py-1 rounded {{ $item->isDevice() ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600' }}">
                                {{ $item->type }}
                            </span>
                            <span class="font-medium">{{ $item->name }}</span>
                            @if($item->isDevice())
                                @php
                                    $displayCount = count($item->settings['display_capabilities'] ?? []);
                                @endphp
                                @if($displayCount > 0)
                                    <span class="text-xs text-gray-500">({{ $displayCount }} sensors shown)</span>
                                @endif
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if($item->isDevice())
                                <a href="{{ route('admin.items.settings', $item) }}"
                                   class="text-blue-600 hover:text-blue-800 text-sm">Configure</a>
                            @endif
                            <form action="{{ route('admin.items.destroy', $item) }}" method="POST"
                                  onsubmit="return confirm('Remove this item?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Remove</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No items added yet. Add devices or flows from the sidebar.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Available Devices/Flows -->
    <div class="lg:sticky lg:top-6 space-y-6">
        @if(empty($devices) && empty($flows))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-yellow-800 text-sm">
                    No devices or flows found. Please configure your
                    <a href="{{ route('admin.settings.index') }}" class="underline">Homey settings</a> first.
                </p>
            </div>
        @endif

        <!-- Devices -->
        @php
            $addedDeviceIds = $dashboard->items->where('type', 'device')->pluck('homey_id')->toArray();
            $availableDevices = collect($devices)->filter(fn($d, $id) => !in_array($id, $addedDeviceIds));
        @endphp
        <div class="bg-white rounded-lg shadow-lg border-2 border-blue-200">
            <div class="p-4 bg-blue-50 border-b border-blue-200 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-blue-900 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                        Available Devices
                    </h2>
                    <span class="bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded-full">
                        {{ $availableDevices->count() }}
                    </span>
                </div>
                <p class="text-xs text-blue-700 mt-1">Click to add to dashboard</p>
            </div>
            <div class="max-h-80 overflow-y-auto p-2">
                @forelse($availableDevices as $id => $device)
                    <form action="{{ route('admin.dashboards.items.store', $dashboard) }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="device">
                        <input type="hidden" name="homey_id" value="{{ $id }}">
                        <input type="hidden" name="name" value="{{ $device['name'] ?? 'Unknown' }}">
                        <button type="submit" class="w-full text-left p-3 hover:bg-blue-50 rounded-lg flex items-center gap-3 transition-colors border border-transparent hover:border-blue-200">
                            <span class="w-3 h-3 rounded-full bg-blue-500 flex-shrink-0"></span>
                            <span class="font-medium text-gray-700">{{ $device['name'] ?? 'Unknown Device' }}</span>
                            <svg class="w-4 h-4 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </form>
                @empty
                    <p class="text-gray-500 text-sm py-4 text-center">All devices added!</p>
                @endforelse
            </div>
        </div>

        <!-- Flows -->
        @php
            $addedFlowIds = $dashboard->items->where('type', 'flow')->pluck('homey_id')->toArray();
            $availableFlows = collect($flows)->filter(fn($f, $id) => !in_array($id, $addedFlowIds));
        @endphp
        <div class="bg-white rounded-lg shadow-lg border-2 border-purple-200">
            <div class="p-4 bg-purple-50 border-b border-purple-200 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-purple-900 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Available Flows
                    </h2>
                    <span class="bg-purple-600 text-white text-xs font-bold px-2 py-1 rounded-full">
                        {{ $availableFlows->count() }}
                    </span>
                </div>
                <p class="text-xs text-purple-700 mt-1">Click to add to dashboard</p>
            </div>
            <div class="max-h-80 overflow-y-auto p-2">
                @forelse($availableFlows as $id => $flow)
                    <form action="{{ route('admin.dashboards.items.store', $dashboard) }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="flow">
                        <input type="hidden" name="homey_id" value="{{ $id }}">
                        <input type="hidden" name="name" value="{{ $flow['name'] ?? 'Unknown' }}">
                        <button type="submit" class="w-full text-left p-3 hover:bg-purple-50 rounded-lg flex items-center gap-3 transition-colors border border-transparent hover:border-purple-200">
                            <span class="w-3 h-3 rounded-full bg-purple-500 flex-shrink-0"></span>
                            <span class="font-medium text-gray-700">{{ $flow['name'] ?? 'Unknown Flow' }}</span>
                            <svg class="w-4 h-4 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </form>
                @empty
                    <p class="text-gray-500 text-sm py-4 text-center">All flows added!</p>
                @endforelse
            </div>
        </div>

        <!-- Refresh Cache -->
        <form action="{{ route('admin.dashboards.edit', $dashboard) }}" method="GET">
            <button type="submit" class="w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh Device/Flow List
            </button>
        </form>
    </div>
</div>

<script>
function copyUrl() {
    const input = document.getElementById('dashboard-url');
    input.select();
    document.execCommand('copy');
    alert('URL copied to clipboard!');
}
</script>
@endsection
