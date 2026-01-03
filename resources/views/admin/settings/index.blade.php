@extends('layouts.admin')
@section('title', 'Settings')

@section('content')
<h1 class="text-2xl font-bold mb-6">Homey Settings</h1>

<div class="max-w-xl bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.settings.store') }}" method="POST">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input type="text" name="name"
                       placeholder="My Homey Pro"
                       value="{{ old('name', $settings?->name ?? 'My Homey') }}"
                       class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       required>
                <p class="text-xs text-gray-500 mt-1">A friendly name for your Homey</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">IP Address</label>
                <input type="text" name="ip_address"
                       placeholder="192.168.1.100"
                       value="{{ old('ip_address', $settings?->ip_address ?? '') }}"
                       class="w-full border rounded-lg px-3 py-2 font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       required>
                <p class="text-xs text-gray-500 mt-1">The local IP address of your Homey (e.g., 192.168.1.100)</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">API Token</label>
                <textarea name="token" rows="3"
                       placeholder="Enter your Homey API token"
                       class="w-full border rounded-lg px-3 py-2 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       required>{{ old('token', $settings ? '••••••••••••••••' : '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">
                    Create an API key in the Homey app: Settings &rarr; API Keys &rarr; Create new key
                </p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Save Settings
                </button>
                <button type="button" id="test-connection"
                        class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">
                    Test Connection
                </button>
            </div>
        </div>
    </form>

    <div id="test-result" class="mt-4 hidden p-3 rounded-lg"></div>
</div>

@if($settings)
<div class="max-w-xl mt-6 bg-gray-50 rounded-lg p-4">
    <h3 class="font-medium text-gray-700 mb-2">Current Configuration</h3>
    <dl class="text-sm space-y-1">
        <div class="flex">
            <dt class="text-gray-500 w-24">Name:</dt>
            <dd>{{ $settings->name }}</dd>
        </div>
        <div class="flex">
            <dt class="text-gray-500 w-24">IP Address:</dt>
            <dd class="font-mono">{{ $settings->ip_address }}</dd>
        </div>
        <div class="flex">
            <dt class="text-gray-500 w-24">API URL:</dt>
            <dd class="font-mono text-xs">{{ $settings->base_url }}</dd>
        </div>
        <div class="flex items-center">
            <dt class="text-gray-500 w-24">Status:</dt>
            <dd>
                @if($connectionStatus && $connectionStatus['success'])
                    <span class="inline-flex items-center gap-1 text-green-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Connected ({{ $connectionStatus['device_count'] }} devices, {{ $connectionStatus['flow_count'] }} flows)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-red-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        Connection failed
                    </span>
                @endif
            </dd>
        </div>
    </dl>
</div>
@endif

<script>
document.getElementById('test-connection').addEventListener('click', async function() {
    const result = document.getElementById('test-result');
    const ipAddress = document.querySelector('input[name="ip_address"]').value;
    const token = document.querySelector('textarea[name="token"]').value;

    result.classList.remove('hidden', 'bg-green-100', 'bg-red-100');
    result.classList.add('bg-gray-100');
    result.innerHTML = '<span class="text-gray-600">Testing connection to Homey...</span>';

    try {
        const response = await axios.post('{{ route("admin.settings.test") }}', {
            ip_address: ipAddress,
            token: token
        });
        if (response.data.success) {
            result.classList.remove('bg-gray-100');
            result.classList.add('bg-green-100');
            result.innerHTML = `<span class="text-green-700">Connected! Found ${response.data.device_count} devices and ${response.data.flow_count} flows.</span>`;
        } else {
            result.classList.remove('bg-gray-100');
            result.classList.add('bg-red-100');
            result.innerHTML = `<span class="text-red-700">${response.data.message || 'Connection failed. Check your IP address and token.'}</span>`;
        }
    } catch (e) {
        result.classList.remove('bg-gray-100');
        result.classList.add('bg-red-100');
        result.innerHTML = '<span class="text-red-700">Error: ' + (e.response?.data?.message || e.message) + '</span>';
    }
});
</script>
@endsection
