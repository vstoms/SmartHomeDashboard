<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>@yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'])
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        body { touch-action: manipulation; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    @yield('content')
</body>
</html>
