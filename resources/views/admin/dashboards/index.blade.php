@extends('layouts.admin')
@section('title', 'Dashboards')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Dashboards</h1>
    <a href="{{ route('admin.dashboards.create') }}"
       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Create Dashboard
    </a>
</div>

<div class="grid gap-4">
    @forelse($dashboards as $dashboard)
        <div class="bg-white rounded-lg shadow p-4 flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-lg">{{ $dashboard->name }}</h2>
                @if($dashboard->description)
                    <p class="text-gray-500 text-sm">{{ $dashboard->description }}</p>
                @endif
                <div class="mt-2 flex items-center gap-4 text-sm">
                    <span class="text-gray-400">
                        {{ $dashboard->items->where('type', 'device')->count() }} devices,
                        {{ $dashboard->items->where('type', 'flow')->count() }} flows
                    </span>
                    <a href="{{ route('dashboard.show', $dashboard) }}"
                       target="_blank"
                       class="text-blue-600 hover:underline">
                        View Dashboard
                    </a>
                    @if(!$dashboard->is_active)
                        <span class="text-orange-500 text-xs">(Inactive)</span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.dashboards.edit', $dashboard) }}"
                   class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Edit</a>
                <form action="{{ route('admin.dashboards.destroy', $dashboard) }}"
                      method="POST"
                      onsubmit="return confirm('Delete this dashboard?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
            <p class="mb-4">No dashboards yet. Create your first one!</p>
            <a href="{{ route('admin.dashboards.create') }}"
               class="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Create Dashboard
            </a>
        </div>
    @endforelse
</div>
@endsection
