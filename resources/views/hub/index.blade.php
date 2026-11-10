<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50/50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DataMind Studio - Inicio y Selección de Ecosistema</title>

    <!-- Scripts Head: Theme Manager con Cero-FOUC -->
    <script src="{{ asset('js/theme_manager.js') }}"></script>

    <!-- CSS Globales / Plugins Locales (Zero CDNs) -->
    <link rel="stylesheet" href="{{ asset('css/global_styles/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/toastr.min.css') }}">
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
</head>
<body class="min-h-full flex flex-col justify-between text-slate-800 antialiased selection:bg-datamind-primary selection:text-white">

    <!-- Barra Superior Minimalista Full-Width -->
    <header class="w-full px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="h-10 w-10 rounded-none bg-datamind-gradient flex items-center justify-center text-white border border-slate-200/50 shadow-xs font-bold text-xl">
                <i class="fa-solid fa-brain"></i>
            </span>
            <div>
                <span class="text-base font-bold tracking-tight text-slate-900">DATAMIND <span class="text-datamind-primary">STUDIO</span></span>
                <p class="text-[11px] text-datamind-secondary font-medium">Plataforma Analítica & RAG</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button id="btnQuickToggleTheme" type="button" class="h-9 w-9 rounded-none border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 transition cursor-pointer" title="Modo Claro / Oscuro">
                <i class="fa-solid fa-moon text-datamind-primary"></i>
            </button>

            @auth
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold px-3 py-1.5 rounded-none bg-white border border-slate-200 text-slate-700">
                        <i class="fa-solid fa-circle-user text-datamind-primary mr-1"></i> {{ auth()->user()->name }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="h-9 w-9 rounded-none border border-slate-200 bg-white hover:bg-red-50 hover:text-red-600 text-slate-600 flex items-center justify-center transition cursor-pointer" title="Cerrar sesión">
                            <i class="fa-solid fa-right-from-bracket text-xs"></i>
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="text-xs font-semibold px-4 py-2 rounded-none btn-datamind-primary">
                    Iniciar Sesión
                </a>
            @endauth
        </div>
    </header>

    <!-- Contenido Central: Selector de Ecosistemas Full-Width -->
    <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-10 flex flex-col items-center justify-center">

        <!-- Título y Subtítulo Central -->
        <div class="text-center mb-12">
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 mb-2">
                Inicio
            </h1>
            <p class="text-sm sm:text-base text-slate-500 font-normal">
                Selecciona un módulo operativo para continuar
            </p>
        </div>

        <!-- Cuadrícula de Tarjetas de Ecosistemas Estáticas e Inmutables -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-6xl mx-auto">
            @forelse($ecosystems as $eco)
                <div class="bg-white rounded-none border border-slate-200 p-8 shadow-xs hover:border-slate-300 hover:shadow-md transition duration-150 flex flex-col items-center text-center group h-[380px] min-h-[380px] max-h-[380px]">
                    
                    <!-- Ícono con contenedor suave y tamaño constante -->
                    <div class="h-16 w-16 rounded-none border border-slate-100 flex items-center justify-center text-2xl mb-4 shrink-0 transition group-hover:scale-105"
                         style="background-color: {{ $eco->color_preset === 'indigo' ? '#EEF2FF' : ($eco->color_preset === 'emerald' ? '#ECFDF5' : '#EFF6FF') }}; color: {{ $eco->color_preset === 'indigo' ? '#4F46E5' : ($eco->color_preset === 'emerald' ? '#059669' : '#0476D9') }};">
                        <i class="fa-solid {{ $eco->icon }}"></i>
                    </div>

                    <!-- Badge Superior del Módulo -->
                    <span class="text-[10px] font-bold tracking-wider uppercase mb-1.5 shrink-0"
                          style="color: {{ $eco->color_preset === 'indigo' ? '#4F46E5' : ($eco->color_preset === 'emerald' ? '#059669' : '#0476D9') }};">
                        {{ $eco->badge_text ?? ('MÓDULO ' . $eco->order_num) }}
                    </span>

                    <!-- Título del Ecosistema con altura uniforme -->
                    <h2 class="text-base font-bold text-slate-900 mb-2 line-clamp-1 shrink-0">
                        {{ $eco->short_name ?? $eco->name }}
                    </h2>

                    <!-- Descripción con altura fija delimitada -->
                    <p class="text-xs text-slate-500 leading-relaxed overflow-hidden line-clamp-4 h-18 text-center">
                        {{ $eco->description }}
                    </p>

                    <!-- Botón de Ingreso Directo anclado al pie -->
                    <div class="mt-auto pt-4 w-full flex justify-center border-t border-slate-100">
                        <a href="{{ $eco->resolveFirstTargetUrl() }}"
                           class="inline-flex items-center gap-2 text-xs font-bold transition hover:gap-3 py-2 px-4 border border-slate-200 hover:border-slate-300 rounded-none bg-slate-50/50 hover:bg-slate-100/70"
                           style="color: {{ $eco->color_preset === 'indigo' ? '#4F46E5' : ($eco->color_preset === 'emerald' ? '#059669' : '#0476D9') }};">
                            <span>Ingresar</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12 text-sm text-slate-400">
                    No hay ecosistemas activos configurados.
                </div>
            @endforelse
        </div>
    </main>

    <!-- Footer Discreto -->
    <footer class="w-full max-w-7xl mx-auto px-4 py-6 text-center text-xs text-slate-400 border-t border-slate-100">
        DataMind Studio v1.2.0 &bull; Arquitectura Multi-Ecosistema por Base de Datos
    </footer>

    <!-- Scripts Locales Obligatorios (Zero CDNs) -->
    <script src="{{ asset('js/global_js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('js/global_js/all.min.js') }}"></script>
</body>
</html>
