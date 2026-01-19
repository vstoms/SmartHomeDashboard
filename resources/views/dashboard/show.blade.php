@extends('layouts.dashboard')
@section('title', $dashboard->name)

@section('content')
<div class="p-4 md:p-6 max-w-7xl mx-auto transition-all duration-300" id="main-content">
    <header class="mb-6 flex justify-between items-start">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold">{{ $dashboard->name }}</h1>
            @if($dashboard->description)
                <p class="text-gray-400 mt-1">{{ $dashboard->description }}</p>
            @endif
        </div>
        <button id="edit-toggle"
                class="lux-button px-4 py-2 rounded-xl text-sm flex items-center gap-2 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span id="edit-toggle-text">Edit Layout</span>
        </button>
    </header>

    <div id="dashboard-grid"
         class="grid-stack"
         data-dashboard-uuid="{{ $dashboard->uuid }}">

        {{-- Regular Dashboard Items --}}
        @foreach($dashboard->items as $item)
            <div class="grid-stack-item"
                 gs-id="{{ $item->id }}"
                 gs-x="{{ $item->grid_x }}"
                 gs-y="{{ $item->grid_y }}"
                 gs-w="{{ $item->grid_w ?: 1 }}"
                 gs-h="{{ $item->grid_h ?: 1 }}"
                 gs-min-w="1"
                 gs-min-h="1">
                <div class="grid-stack-item-content">
                    @if($item->isDevice())
                        <x-device-card :item="$item" />
                    @else
                        <x-flow-card :item="$item" />
                    @endif
                </div>
                <div class="edit-item-buttons absolute top-2 right-2 flex gap-1 z-50">
                    @if($item->isDevice())
                    <button class="configure-item-btn w-6 h-6 bg-blue-500 hover:bg-blue-600 rounded-full flex items-center justify-center transition-colors"
                            data-item-id="{{ $item->id }}"
                            title="Configure">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                    @endif
                    <button class="remove-item-btn w-6 h-6 bg-red-500 hover:bg-red-600 rounded-full flex items-center justify-center transition-colors"
                            data-item-id="{{ $item->id }}"
                            title="Remove">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach

        {{-- Device Groups (Multi-Switch Cards) --}}
        @foreach($groupsWithDevices as $group)
            <div class="grid-stack-item"
                 gs-id="group-{{ $group['id'] }}"
                 gs-x="{{ $group['grid_x'] }}"
                 gs-y="{{ $group['grid_y'] }}"
                 gs-w="{{ $group['grid_w'] ?: 2 }}"
                 gs-h="{{ $group['grid_h'] ?: 2 }}"
                 gs-min-w="2"
                 gs-min-h="2"
                 data-group-id="{{ $group['id'] }}">
                <div class="grid-stack-item-content">
                    <x-multi-switch-card 
                        :title="$group['name']" 
                        :devices="$group['devices']"
                    />
                </div>
                <div class="edit-item-buttons absolute top-2 right-2 flex gap-1 z-50">
                    <button class="configure-group-btn w-6 h-6 bg-amber-500 hover:bg-amber-600 rounded-full flex items-center justify-center transition-colors"
                            data-group-id="{{ $group['id'] }}"
                            title="Configure Group">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                    <button class="remove-group-btn w-6 h-6 bg-red-500 hover:bg-red-600 rounded-full flex items-center justify-center transition-colors"
                            data-group-id="{{ $group['id'] }}"
                            title="Remove Group">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if($dashboard->items->isEmpty())
        <div id="empty-state" class="lux-panel rounded-2xl p-8 text-center">
            <p class="text-gray-400">No devices or flows added to this dashboard yet.</p>
            <p class="text-gray-500 text-sm mt-2">Click "Edit Layout" to add devices and flows.</p>
        </div>
    @endif

    <div id="edit-controls" class="lux-panel fixed bottom-0 left-0 right-0 p-4 transform translate-y-full transition-transform duration-300 z-40">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <p class="text-gray-400 text-sm">Drag to move, resize from corners</p>
            <div class="flex gap-3">
                <button id="cancel-edit" class="lux-button px-4 py-2 rounded-xl text-sm">
                    Cancel
                </button>
                <button id="save-layout" class="lux-button lux-button-primary px-4 py-2 rounded-xl text-sm">
                    Save Layout
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Items Sidebar -->
<div id="add-items-panel" class="lux-panel fixed top-0 right-0 h-full w-80 transform translate-x-full transition-transform duration-300 z-50 flex flex-col">
    <div class="p-4 border-b border-white/10 flex justify-between items-center">
        <h2 class="text-lg font-semibold">Add Items</h2>
        <button id="close-add-panel" class="p-1 hover:bg-white/10 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Search Box -->
    <div class="p-4 border-b border-white/10">
        <div class="relative">
            <input type="text"
                   id="items-search"
                   class="lux-input w-full rounded-lg pl-10 pr-8 py-2 text-sm bg-gray-800/50 border border-white/10 text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="Search devices and flows..."
                   autocomplete="off">
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <button id="clear-search" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white hidden">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <!-- Search Scope Filter -->
        <div class="flex gap-2 mt-2">
            <button class="search-filter-btn text-xs px-3 py-1 rounded-full bg-blue-600/20 text-blue-400 border border-blue-500/30" data-scope="all">All</button>
            <button class="search-filter-btn text-xs px-3 py-1 rounded-full bg-gray-700/50 text-gray-400 border border-transparent hover:bg-gray-700 hover:text-gray-300" data-scope="devices">Devices</button>
            <button class="search-filter-btn text-xs px-3 py-1 rounded-full bg-gray-700/50 text-gray-400 border border-transparent hover:bg-gray-700 hover:text-gray-300" data-scope="flows">Flows</button>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-4">
        <!-- Create Device Group Button -->
        <div>
            <button id="create-group-btn" class="w-full lux-button px-4 py-3 rounded-xl text-sm flex items-center justify-center gap-2 bg-amber-500/20 border-amber-500/30 text-amber-400 hover:bg-amber-500/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Create Multi-Switch Group
            </button>
            <p class="text-xs text-gray-500 mt-1 text-center">Group multiple devices into one card</p>
        </div>

        <div class="border-t border-white/10 pt-4">
            <!-- Devices Section -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-blue-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                        Devices
                    </h3>
                    <span id="devices-count" class="lux-pill text-xs font-bold px-2 py-0.5 rounded-full">0</span>
                </div>
                <div id="available-devices" class="space-y-1 max-h-60 overflow-y-auto">
                    <p class="text-gray-500 text-sm py-2 text-center">Loading...</p>
                </div>
            </div>
        </div>

        <!-- Flows Section -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-purple-400 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Flows
                </h3>
                <span id="flows-count" class="lux-pill lux-pill-purple text-xs font-bold px-2 py-0.5 rounded-full">0</span>
            </div>
            <div id="available-flows" class="space-y-1 max-h-60 overflow-y-auto">
                <p class="text-gray-500 text-sm py-2 text-center">Loading...</p>
            </div>
        </div>
    </div>

    <div class="p-4 border-t border-white/10">
        <button id="refresh-items" class="lux-button w-full px-4 py-2 rounded-xl text-sm flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh List
        </button>
    </div>
</div>

<div id="toast" class="lux-panel fixed bottom-4 right-4 text-white px-4 py-2 rounded-lg shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 z-50">
    <span id="toast-message"></span>
</div>

<!-- Configure Item Modal -->
<div id="configure-modal" class="fixed inset-0 bg-black/50 z-[100] hidden items-center justify-center">
    <div class="lux-panel rounded-xl w-full max-w-md mx-4 max-h-[90vh] flex flex-col">
        <div class="p-4 border-b border-white/10 flex justify-between items-center">
            <h2 class="text-lg font-semibold" id="configure-modal-title">Configure Device</h2>
            <button id="close-configure-modal" class="p-1 hover:bg-white/10 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Display Name</label>
                <input type="text" id="configure-name" class="lux-input w-full rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div id="configure-controls" class="space-y-2">
                <h3 class="text-sm font-medium text-gray-400">Controls</h3>
                <div id="configure-controls-list" class="space-y-2">
                    <!-- Populated by JS -->
                </div>
            </div>

            <div id="configure-sensors">
                <h3 class="text-sm font-medium text-gray-400 mb-2">Display Sensors</h3>
                <div id="configure-sensors-list" class="space-y-1 max-h-48 overflow-y-auto">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-white/10 flex gap-3">
            <button id="configure-cancel" class="lux-button flex-1 px-4 py-2 rounded-xl">
                Cancel
            </button>
            <button id="configure-save" class="lux-button lux-button-primary flex-1 px-4 py-2 rounded-xl">
                Save
            </button>
        </div>
    </div>
</div>

<!-- Create/Configure Device Group Modal -->
<div id="group-modal" class="fixed inset-0 bg-black/50 z-[100] hidden items-center justify-center">
    <div class="lux-panel rounded-xl w-full max-w-lg mx-4 max-h-[90vh] flex flex-col">
        <div class="p-4 border-b border-white/10 flex justify-between items-center">
            <h2 class="text-lg font-semibold" id="group-modal-title">Create Device Group</h2>
            <button id="close-group-modal" class="p-1 hover:bg-white/10 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Group Name</label>
                <input type="text" id="group-name" class="lux-input w-full rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="e.g., Living Room Lights">
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-400 mb-2">Select Devices</h3>
                <p class="text-xs text-gray-500 mb-3">Choose devices to include in this group. Only devices with on/off or dimmer capabilities are shown.</p>
                
                <!-- Search within devices -->
                <div class="relative mb-3">
                    <input type="text"
                           id="group-device-search"
                           class="lux-input w-full rounded-lg pl-10 pr-3 py-2 text-sm bg-gray-800/50 border border-white/10 text-white placeholder-gray-500"
                           placeholder="Search devices..."
                           autocomplete="off">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <div id="group-devices-list" class="space-y-1 max-h-64 overflow-y-auto border border-white/10 rounded-lg p-2">
                    <p class="text-gray-500 text-sm py-2 text-center">Loading devices...</p>
                </div>
                
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                    <span id="group-selected-count">0 devices selected</span>
                    <button id="group-select-all" class="text-amber-400 hover:text-amber-300">Select All</button>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-white/10 flex gap-3">
            <button id="group-cancel" class="lux-button flex-1 px-4 py-2 rounded-xl">
                Cancel
            </button>
            <button id="group-save" class="lux-button flex-1 px-4 py-2 rounded-xl bg-amber-500/20 border-amber-500/30 text-amber-400 hover:bg-amber-500/30">
                <span id="group-save-text">Create Group</span>
            </button>
        </div>
    </div>
</div>
@endsection
