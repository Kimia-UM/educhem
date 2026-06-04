<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'EduChem POE') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @fonts

    {{-- WAJIB ADA: @routes untuk Ziggy agar fungsi route() dikenali di Vue --}}
    @routes

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    
    @inertiaHead
</head>
{{-- Dark mode dimatikan paksa. Menggunakan bg-slate-50 untuk nuansa Modern SaaS --}}
<body class="font-sans antialiased bg-slate-50 text-slate-900">
    @inertia
</body>
</html>