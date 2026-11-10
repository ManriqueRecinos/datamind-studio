@props([
    'title' => 'DataMind Studio',
    'ecosystem' => 'chat',
    'ecosystemLabel' => 'Ecosistema de Chat & Analítica',
    'ecosystemIcon' => 'fa-comments',
    'activeModule' => 'Consola Conversacional IA',
    'activeContext' => 'Listo para consultas',
    'isOnline' => true,
    'fullHeight' => true,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - DataMind Studio</title>

    <!-- Scripts Head: Theme Manager con Cero-FOUC -->
    <script src="{{ asset('js/theme_manager.js') }}"></script>

    <!-- CSS Globales / Plugins Locales (Zero CDNs) -->
    <link rel="stylesheet" href="{{ asset('css/global_styles/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/globales.css') }}">

    <!-- Vite Styles (Tailwind CSS v4) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body {
            font-family: var(--datamind-font-family, 'Inter', system-ui, -apple-system, sans-serif);
            font-size: var(--datamind-font-size, 14px);
            background-color: var(--datamind-bg-neutral);
        }
    </style>
    {{ $head ?? '' }}
</head>
<body class="h-full flex flex-col text-slate-800 antialiased selection:bg-datamind-primary selection:text-white {{ $fullHeight ? 'overflow-hidden' : '' }}">

    <!-- Header y Context Sub-Nav Recto Unificado -->
    <x-ecosystem-header
        :ecosystem="$ecosystem"
        :ecosystemLabel="$ecosystemLabel"
        :ecosystemIcon="$ecosystemIcon"
        :activeModule="$activeModule"
        :activeContext="$activeContext"
        :isOnline="$isOnline"
    />

    <!-- Contenido Principal Dinámico -->
    {{ $slot }}

    <!-- Modales Adicionales Registrados por la Vista -->
    {{ $modals ?? '' }}

    <!-- Scripts Base Obligatorios (Zero CDNs) -->
    <script src="{{ asset('js/global_js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('js/global_js/all.min.js') }}"></script>
    <script src="{{ asset('js/global_js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/global_js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/global_js/axios.min.js') }}"></script>

    <!-- Scripts Específicos de la Vista -->
    {{ $scripts ?? '' }}
</body>
</html>
