<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Homey Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('admin.dashboards.index') }}" class="text-xl font-bold text-gray-900">
                        Homey Dashboard
                    </a>
                    <a href="{{ route('admin.dashboards.index') }}"
                       class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('admin.dashboards.*') ? 'font-semibold text-gray-900' : '' }}">
                        Dashboards
                    </a>
                    <a href="{{ route('admin.settings.index') }}"
                       class="text-gray-600 hover:text-gray-900 {{ request()->routeIs('admin.settings.*') ? 'font-semibold text-gray-900' : '' }}">
                        Settings
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
