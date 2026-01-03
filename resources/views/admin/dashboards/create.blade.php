@extends('layouts.admin')
@section('title', 'Create Dashboard')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.dashboards.index') }}" class="text-blue-600 hover:underline">
        &larr; Back to Dashboards
    </a>
</div>

<div class="max-w-xl bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-6">Create Dashboard</h1>

    <form action="{{ route('admin.dashboards.store') }}" method="POST">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Living Room" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Description (optional)</label>
                <textarea name="description" rows="2"
                          class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Controls for the living room">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Create Dashboard
            </button>
        </div>
    </form>
</div>
@endsection
