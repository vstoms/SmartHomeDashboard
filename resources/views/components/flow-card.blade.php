@props(['item'])

<button class="flow-card bg-purple-900 hover:bg-purple-800 rounded-2xl p-4 md:p-5 min-h-[140px] flex flex-col justify-center items-center transition-all duration-200 active:scale-95 touch-manipulation"
        data-flow-id="{{ $item->homey_id }}"
        data-item-id="{{ $item->id }}">

    <div class="w-12 h-12 bg-purple-700 rounded-full flex items-center justify-center mb-3">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>

    <span class="font-semibold text-center text-sm md:text-base">{{ $item->name }}</span>
    <span class="flow-status text-xs text-purple-300 mt-1">Tap to run</span>
</button>
