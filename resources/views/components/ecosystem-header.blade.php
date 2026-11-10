@props([
    'ecosystem' => 'chat',
    'ecosystemLabel' => 'Ecosistema de Chat & Analítica',
    'ecosystemIcon' => 'fa-comments',
    'activeModule' => 'Consola Conversacional IA',
    'activeContext' => 'Listo para consultas',
    'isOnline' => true,
])

@php
    $otherEcosystems = collect([]);
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('ecosystems')) {
            $otherEcosystems = \App\Models\Ecosystem::where('is_active', true)
                ->where('key', '!=', $ecosystem)
                ->orderBy('order_num')
                ->get();
        }
    } catch (\Throwable $e) {
        $otherEcosystems = collect([]);
    }
@endphp

<!-- NIVEL 1: Navbar Global Monolítico Recto (0px Border Radius / De Extremo a Extremo) -->
<header class="bg-white border-b border-slate-200 shrink-0 z-40 w-full rounded-none">
    <div class="w-full px-4 sm:px-6 h-14 flex items-center justify-between gap-3">
        <!-- Izquierda: Identidad de Marca + Botón Switcher Recto de Ecosistema -->
        <div class="flex items-center gap-4 min-w-0">
            <a href="{{ route('hub.index') }}" class="h-9 w-9 bg-datamind-gradient flex items-center justify-center text-white font-bold text-base hover:opacity-95 transition shrink-0 rounded-none shadow-none" title="Ir al Hub de Inicio">
                <i class="fa-solid fa-brain"></i>
            </a>
            <div class="min-w-0 flex items-center gap-2.5">
                <span class="text-sm sm:text-base font-extrabold tracking-tight text-slate-900 truncate">
                    DATAMIND <span class="text-datamind-primary">STUDIO</span>
                </span>
                <span class="hidden sm:inline-flex text-[10px] font-bold px-2 py-0.5 rounded-none border border-datamind-light/80 bg-datamind-neutral text-datamind-dark uppercase">
                    v1.4.0
                </span>
            </div>

            <div class="h-5 w-[1px] bg-slate-200 hidden sm:block"></div>

            <!-- BOTÓN ÚNICO RECTO CON BURBUJA FLOTANTE HOVER (SWITCHER DE ECOSISTEMAS) -->
            <div class="relative group">
                <button type="button"
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-datamind-neutral hover:bg-slate-100 border border-slate-200 text-xs font-bold text-slate-800 transition rounded-none cursor-pointer">
                    <i class="fa-solid {{ $ecosystemIcon }} text-datamind-primary text-xs"></i>
                    <span class="truncate max-w-[140px] sm:max-w-[200px]">{{ $ecosystemLabel }}</span>
                    <i class="fa-solid fa-shapes text-slate-400 text-[10px] group-hover:rotate-12 transition"></i>
                </button>

                <!-- Burbuja Flotante Desplegable en Hover (Totalmente Recta y Cuadrada) -->
                <div class="absolute left-0 top-full pt-1.5 w-72 hidden group-hover:block z-50 transition-all duration-150">
                    <div class="bg-white border border-slate-300 shadow-xl p-3 rounded-none">
                        <div class="px-2 pb-2 mb-2 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                Cambiar Ecosistema
                            </span>
                            <a href="{{ route('hub.index') }}" class="text-[10px] font-bold text-datamind-primary hover:underline flex items-center gap-1">
                                <i class="fa-solid fa-grip"></i> Ver Hub
                            </a>
                        </div>

                        <div class="space-y-1">
                            @forelse($otherEcosystems as $otherEco)
                                <a href="{{ $otherEco->resolveFirstTargetUrl() }}"
                                   class="flex items-center gap-2.5 p-2 border border-transparent hover:border-slate-300 hover:bg-datamind-neutral transition rounded-none group/item">
                                    <div class="h-7 w-7 flex items-center justify-center text-xs shrink-0 rounded-none"
                                         style="background-color: {{ $otherEco->color_preset === 'indigo' ? '#EEF2FF' : ($otherEco->color_preset === 'emerald' ? '#ECFDF5' : '#EFF6FF') }}; color: {{ $otherEco->color_preset === 'indigo' ? '#4F46E5' : ($otherEco->color_preset === 'emerald' ? '#059669' : '#0476D9') }};">
                                        <i class="fa-solid {{ $otherEco->icon }}"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-800 group-hover/item:text-datamind-dark truncate">
                                                {{ $otherEco->short_name }}
                                            </span>
                                            <span class="text-[9px] font-bold px-1 py-0.2 rounded-none bg-slate-100 text-slate-600 border border-slate-200">
                                                {{ $otherEco->badge_text }}
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="p-2 text-center text-xs text-slate-400">
                                    No hay otros ecosistemas disponibles.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Derecha: Estado Motor IA, Modo Oscuro y Menú de Usuario -->
        <div class="flex items-center gap-2">
            {{ $rightActions ?? '' }}

            @if($isOnline)
                <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-none" title="Microservicio FastAPI conectado">
                    <span class="h-2 w-2 bg-emerald-500 rounded-none animate-pulse"></span>
                    <span class="hidden xl:inline">Motor IA Activo</span>
                </span>
            @else
                <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 rounded-none" title="FastAPI offline">
                    <span class="h-2 w-2 bg-amber-500 rounded-none"></span>
                    <span class="hidden xl:inline">Offline</span>
                </span>
            @endif

            <!-- Botón Rápido Modo Claro / Oscuro -->
            <button id="btnQuickToggleTheme" type="button"
                    class="h-9 w-9 bg-datamind-neutral hover:bg-slate-100 border border-slate-200 flex items-center justify-center transition cursor-pointer rounded-none"
                    title="Alternar Modo Claro / Oscuro">
                <i class="fa-solid fa-moon text-datamind-primary text-xs"></i>
            </button>

            <!-- Menú Desplegable de Usuario (Totalmente Recto) -->
            <div class="relative">
                <button id="btnUserProfileMenu" type="button"
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-datamind-neutral hover:bg-slate-100 border border-slate-200 text-xs font-bold text-slate-800 transition cursor-pointer rounded-none">
                    <span class="h-5 w-5 bg-datamind-gradient text-white flex items-center justify-center text-[10px] rounded-none">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <span class="hidden sm:inline max-w-[120px] truncate">
                        @auth {{ auth()->user()->name }} @else Mi Cuenta @endauth
                    </span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400"></i>
                </button>

                <div id="userProfileDropdown" class="hidden absolute right-0 mt-1.5 w-60 bg-white border border-slate-300 shadow-xl py-2 z-50 rounded-none">
                    <div class="px-4 py-2 border-b border-slate-100">
                        <p class="text-xs font-bold text-slate-900 truncate">
                            @auth {{ auth()->user()->name }} @else Usuario Invitado @endauth
                        </p>
                        <p class="text-[10px] text-slate-500 truncate">
                            @auth {{ auth()->user()->email }} @else Sesión local activa @endauth
                        </p>
                    </div>

                    <div class="py-1 border-b border-slate-100">
                        <a href="{{ route('settings.index') }}#profileSection" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-datamind-neutral hover:text-datamind-primary transition">
                            <i class="fa-solid fa-id-badge text-datamind-primary w-4 text-center"></i>
                            <span>Ver mi Perfil</span>
                        </a>
                        <a href="{{ route('settings.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-datamind-neutral hover:text-datamind-primary transition">
                            <i class="fa-solid fa-palette text-datamind-primary w-4 text-center"></i>
                            <span>Colores, Fuente y Tema</span>
                        </a>
                        <a href="{{ route('hub.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-datamind-neutral hover:text-datamind-primary transition">
                            <i class="fa-solid fa-grip text-slate-400 w-4 text-center"></i>
                            <span>Hub de Inicio</span>
                        </a>
                    </div>

                    <div class="pt-1">
                        @auth
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50 transition cursor-pointer">
                                    <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
                                    <span>Cerrar Sesión</span>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-datamind-primary hover:bg-datamind-neutral transition">
                                <i class="fa-solid fa-user-lock w-4 text-center"></i>
                                <span>Iniciar Sesión</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- NIVEL 2: Mini-Navbar Contextual Adosado al Navbar (Totalmente Recto, Sin Márgenes, Sin Separación) -->
<div class="ecosystem-subnav bg-slate-50 border-b border-slate-200 shrink-0 z-30 w-full rounded-none">
    <div class="w-full px-4 sm:px-6 h-8 flex items-center justify-between gap-3 text-xs">
        <!-- Ruta Humana de Posición Actual -->
        <div class="flex items-center gap-2 min-w-0 overflow-x-auto whitespace-nowrap">
            <a href="{{ route('hub.index') }}" class="text-slate-400 hover:text-datamind-primary transition flex items-center gap-1 font-semibold" title="Volver al Hub de Inicio">
                <i class="fa-solid fa-house text-[10px]"></i>
                <span class="hidden sm:inline">Inicio</span>
            </a>
            <i class="fa-solid fa-chevron-right text-[8px] text-slate-300 shrink-0"></i>

            <span class="font-bold text-slate-800 flex items-center gap-1.5 shrink-0">
                <i class="fa-solid {{ $ecosystemIcon }} text-datamind-primary text-[10px]"></i>
                <span>{{ $ecosystemLabel }}</span>
            </span>

            <i class="fa-solid fa-chevron-right text-[8px] text-slate-300 shrink-0"></i>

            <span id="subnavActiveModule" class="font-semibold text-slate-700 bg-white px-2 py-0.5 border border-slate-200 shrink-0 rounded-none">
                {{ $activeModule }}
            </span>

            <i class="fa-solid fa-chevron-right text-[8px] text-slate-300 shrink-0"></i>

            <span id="subnavActiveContext" class="text-slate-500 truncate font-mono text-[11px]">
                {{ $activeContext }}
            </span>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="hidden md:inline-flex items-center gap-1 text-[10px] text-slate-400 font-medium">
                <i class="fa-solid fa-shield-halved text-emerald-500 text-[10px]"></i> Entorno Seguro On-Premise
            </span>
        </div>
    </div>
</div>
