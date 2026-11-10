<x-app-layout
    title="Configuración & Apariencia"
    ecosystem="settings"
    ecosystemLabel="Ecosistema de Configuración & Apariencia"
    ecosystemIcon="fa-gear"
    activeModule="Personalización Visual"
    activeContext="Paletas y Tipografía"
    :isOnline="$isOnline ?? false"
    :fullHeight="true">

    <!-- Layout Monolítico Recto de 2 Columnas Adosadas (Sin Espacios Muertos, Sin Bordes Redondeados) -->
    <main class="flex-1 w-full flex overflow-hidden rounded-none">

        <!-- Sidebar Izquierdo: Opciones del Ecosistema desde Base de Datos Adosado -->
        <aside class="hidden lg:flex flex-col w-80 shrink-0 bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar rounded-none">
            <x-ecosystem-sidebar ecosystemKey="settings" />

            <div class="p-3.5 border-b border-slate-200 bg-white">
                <button id="btnResetDefaultTheme" type="button" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 text-slate-700 hover:bg-slate-50 transition flex items-center justify-center gap-1.5 cursor-pointer rounded-none">
                    <i class="fa-solid fa-arrow-rotate-left"></i> Restaurar Valores Originales
                </button>
            </div>

            <!-- Widget Informativo Recto -->
            <div class="p-3.5 bg-slate-50/50 text-xs text-slate-600">
                <div class="flex items-center gap-1.5 font-bold text-slate-800 mb-1">
                    <i class="fa-solid fa-bolt text-amber-500"></i> Aplicación Reactiva
                </div>
                <p class="leading-relaxed text-[11px] text-slate-500">
                    Los cambios de paleta, color o fuente se aplican de inmediato en toda la suite sin necesidad de recargar la página.
                </p>
            </div>
        </aside>

        <!-- Contenido Central: Paneles de Personalización Rectos Adosados -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Barra de Pestañas Horizontales de Navegación Rápida -->
            @include('settings.partials.settings-nav-tabs', ['activeTab' => 'appearance'])

            <div class="flex-1 p-6 overflow-y-auto custom-scrollbar flex flex-col gap-6 bg-slate-50/30">

            <!-- SECCIÓN 1: Paletas Predefinidas y Selectores Hexadecimales Rectos -->
            <x-panel id="paletteSection" title="Paletas Corporativas Predefinidas" icon="fa-palette">
                <p class="text-xs text-slate-500 mb-4">Elige un tema cromático optimizado para analítica empresarial</p>

                <!-- Tarjetas de Paletas Predefinidas Cuadradas -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6" id="presetPalettesGrid">
                    <button type="button" class="palette-preset-card p-3 border-2 border-datamind-primary bg-datamind-neutral flex flex-col items-center gap-2 cursor-pointer transition rounded-none" data-preset="ocean">
                        <div class="flex h-4 w-full rounded-none overflow-hidden">
                            <span class="flex-1" style="background:#0476D9"></span>
                            <span class="flex-1" style="background:#0468BF"></span>
                            <span class="flex-1" style="background:#4B89BF"></span>
                            <span class="flex-1" style="background:#99D0F2"></span>
                        </div>
                        <span class="text-xs font-bold text-slate-800">Ocean Blue</span>
                    </button>

                    <button type="button" class="palette-preset-card p-3 border border-slate-200 bg-white flex flex-col items-center gap-2 cursor-pointer transition rounded-none" data-preset="emerald">
                        <div class="flex h-4 w-full rounded-none overflow-hidden">
                            <span class="flex-1" style="background:#059669"></span>
                            <span class="flex-1" style="background:#047857"></span>
                            <span class="flex-1" style="background:#10B981"></span>
                            <span class="flex-1" style="background:#A7F3D0"></span>
                        </div>
                        <span class="text-xs font-bold text-slate-800">Emerald Mint</span>
                    </button>

                    <button type="button" class="palette-preset-card p-3 border border-slate-200 bg-white flex flex-col items-center gap-2 cursor-pointer transition rounded-none" data-preset="indigo">
                        <div class="flex h-4 w-full rounded-none overflow-hidden">
                            <span class="flex-1" style="background:#4F46E5"></span>
                            <span class="flex-1" style="background:#4338CA"></span>
                            <span class="flex-1" style="background:#6366F1"></span>
                            <span class="flex-1" style="background:#C7D2FE"></span>
                        </div>
                        <span class="text-xs font-bold text-slate-800">Indigo Velvet</span>
                    </button>

                    <button type="button" class="palette-preset-card p-3 border border-slate-200 bg-white flex flex-col items-center gap-2 cursor-pointer transition rounded-none" data-preset="amber">
                        <div class="flex h-4 w-full rounded-none overflow-hidden">
                            <span class="flex-1" style="background:#D97706"></span>
                            <span class="flex-1" style="background:#B45309"></span>
                            <span class="flex-1" style="background:#F59E0B"></span>
                            <span class="flex-1" style="background:#FDE68A"></span>
                        </div>
                        <span class="text-xs font-bold text-slate-800">Amber Golden</span>
                    </button>

                    <button type="button" class="palette-preset-card p-3 border border-slate-200 bg-white flex flex-col items-center gap-2 cursor-pointer transition rounded-none" data-preset="graphite">
                        <div class="flex h-4 w-full rounded-none overflow-hidden">
                            <span class="flex-1" style="background:#334155"></span>
                            <span class="flex-1" style="background:#1E293B"></span>
                            <span class="flex-1" style="background:#64748B"></span>
                            <span class="flex-1" style="background:#CBD5E1"></span>
                        </div>
                        <span class="text-xs font-bold text-slate-800">Graphite Dark</span>
                    </button>
                </div>

                <!-- Selectores de Colores Individuales -->
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-eye-dropper text-datamind-primary"></i> Ajuste de Tokens Cromáticos (HEX)
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                    <div class="p-2.5 border border-slate-200 flex items-center justify-between rounded-none bg-white">
                        <div>
                            <span class="block text-xs font-bold text-slate-800">Primario</span>
                            <span class="block text-[10px] text-slate-400">Botones y Foco</span>
                        </div>
                        <input type="color" id="pickerPrimary" class="h-7 w-9 cursor-pointer border border-slate-300 rounded-none">
                    </div>

                    <div class="p-2.5 border border-slate-200 flex items-center justify-between rounded-none bg-white">
                        <div>
                            <span class="block text-xs font-bold text-slate-800">Primario Oscuro</span>
                            <span class="block text-[10px] text-slate-400">Títulos y Gradientes</span>
                        </div>
                        <input type="color" id="pickerPrimaryDark" class="h-7 w-9 cursor-pointer border border-slate-300 rounded-none">
                    </div>

                    <div class="p-2.5 border border-slate-200 flex items-center justify-between rounded-none bg-white">
                        <div>
                            <span class="block text-xs font-bold text-slate-800">Secundario</span>
                            <span class="block text-[10px] text-slate-400">Acentos y Badges</span>
                        </div>
                        <input type="color" id="pickerSecondary" class="h-7 w-9 cursor-pointer border border-slate-300 rounded-none">
                    </div>

                    <div class="p-2.5 border border-slate-200 flex items-center justify-between rounded-none bg-white">
                        <div>
                            <span class="block text-xs font-bold text-slate-800">Acento Claro</span>
                            <span class="block text-[10px] text-slate-400">Bordes y Hover</span>
                        </div>
                        <input type="color" id="pickerAccentLight" class="h-7 w-9 cursor-pointer border border-slate-300 rounded-none">
                    </div>

                    <div class="p-2.5 border border-slate-200 flex items-center justify-between rounded-none bg-white">
                        <div>
                            <span class="block text-xs font-bold text-slate-800">Fondo Neutral</span>
                            <span class="block text-[10px] text-slate-400">Superficies</span>
                        </div>
                        <input type="color" id="pickerBgNeutral" class="h-7 w-9 cursor-pointer border border-slate-300 rounded-none">
                    </div>
                </div>
            </x-panel>

            <!-- SECCIÓN 2: Tipografía y Escala de Tamaño -->
            <x-panel id="typographySection" title="Tipografía y Tamaño de Texto" icon="fa-font">
                <p class="text-xs text-slate-500 mb-4">Configura la legibilidad de tablas, consultas SQL y gráficos</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1.5">Familia Tipográfica</label>
                        <select id="fontFamilySelector" class="w-full p-2 border border-slate-200 bg-white text-xs font-semibold text-slate-800 rounded-none focus:outline-hidden">
                            <option value="inter">Inter (Predeterminada, Moderna Corporativa)</option>
                            <option value="roboto">Roboto (Clásica y Compacta)</option>
                            <option value="jakarta">Plus Jakarta Sans (Corporativa)</option>
                            <option value="system">Sistema Operativo Nativo</option>
                            <option value="mono">JetBrains Mono (Estilo Técnico)</option>
                            <option value="serif">Georgia / Editorial (Serif)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1.5">Escala de Tamaño</label>
                        <div class="grid grid-cols-5 gap-1.5" id="fontSizeGrid">
                            <button type="button" class="btn-font-size p-2 border border-slate-200 text-xs font-bold hover:bg-slate-50 cursor-pointer text-center rounded-none" data-size="xs">Muy Chico</button>
                            <button type="button" class="btn-font-size p-2 border border-slate-200 text-xs font-bold hover:bg-slate-50 cursor-pointer text-center rounded-none" data-size="sm">Compacto</button>
                            <button type="button" class="btn-font-size p-2 border border-datamind-primary bg-datamind-neutral text-xs font-bold cursor-pointer text-center rounded-none font-extrabold" data-size="base">Estándar</button>
                            <button type="button" class="btn-font-size p-2 border border-slate-200 text-xs font-bold hover:bg-slate-50 cursor-pointer text-center rounded-none" data-size="lg">Grande</button>
                            <button type="button" class="btn-font-size p-2 border border-slate-200 text-xs font-bold hover:bg-slate-50 cursor-pointer text-center rounded-none" data-size="xl">Accesible</button>
                        </div>
                    </div>
                </div>
            </x-panel>

            <!-- SECCIÓN 3: Vista Previa en Vivo -->
            <x-panel title="Vista Previa de Componentes en Vivo" icon="fa-desktop">
                <div class="p-4 bg-datamind-neutral border border-datamind-light flex flex-col gap-3 rounded-none">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-7 w-7 bg-datamind-gradient flex items-center justify-center text-white text-xs font-bold rounded-none">
                                <i class="fa-solid fa-chart-pie"></i>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-datamind-dark">Total Ventas por Región</h4>
                                <span class="text-[10px] text-datamind-secondary">Vista previa con tokens dinámicos</span>
                            </div>
                        </div>
                        <span class="badge-datamind text-xs font-semibold px-2 py-0.5 rounded-none">
                            Activo
                        </span>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" class="btn-datamind-primary text-xs font-semibold px-3 py-1.5 rounded-none flex items-center gap-1.5">
                            <i class="fa-solid fa-sparkles"></i> Botón Primario
                        </button>
                        <button type="button" class="btn-datamind-soft text-xs font-semibold px-3 py-1.5 rounded-none flex items-center gap-1.5">
                            <i class="fa-solid fa-download"></i> Botón Suave
                        </button>
                    </div>
                </div>
            </x-panel>

            <!-- SECCIÓN 4: Mi Perfil -->
            <x-panel id="profileSection" title="Mi Perfil y Cuenta" icon="fa-user-circle">
                @auth
                    <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-none">
                        <div class="h-10 w-10 bg-datamind-gradient flex items-center justify-center text-white text-base font-bold rounded-none">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900">{{ auth()->user()->name }}</h3>
                            <p class="text-[11px] text-slate-500">{{ auth()->user()->email }}</p>
                            <span class="inline-block mt-0.5 text-[9px] font-semibold text-emerald-700 bg-emerald-100 px-1.5 py-0.2 rounded-none">Sesión Activa</span>
                        </div>
                    </div>
                @else
                    <div class="p-3 bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-center justify-between rounded-none">
                        <span>No has iniciado sesión. Puedes navegar como invitado o iniciar sesión.</span>
                        <a href="{{ route('login') }}" class="btn-datamind-primary px-3 py-1.5 font-semibold rounded-none">Iniciar Sesión</a>
                    </div>
                @endauth
            </x-panel>

            </div>
        </div>
    </main>

    <x-slot:scripts>
        <script src="{{ asset('js/settings_manager.js') }}"></script>
    </x-slot:scripts>

</x-app-layout>
