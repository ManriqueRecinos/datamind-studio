<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-datamind-neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DataMind Studio - Gestor de Bases de Datos y Vectorización</title>

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
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--datamind-bg-neutral);
        }
    </style>
</head>
<body class="min-h-full flex flex-col text-slate-800 antialiased selection:bg-datamind-primary selection:text-white">

    <!-- Header / Navbar Superior -->
    <header class="bg-white border-b border-datamind-light/60 sticky top-0 z-30 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- Logotipo e Identidad -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('chat.index') }}" class="h-10 w-10 rounded-xl bg-datamind-gradient flex items-center justify-center text-white shadow-md font-bold text-xl hover:opacity-95 transition">
                        <i class="fa-solid fa-database"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-bold tracking-tight text-slate-900">DATAMIND <span class="text-datamind-primary">STUDIO</span></span>
                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full badge-datamind">
                                Conector Universal &bull; pgvector
                            </span>
                        </div>
                        <p class="text-[11px] text-datamind-secondary font-medium hidden sm:block">Conecta cualquier PostgreSQL o MariaDB/MySQL, explora sus tablas y entrena al asistente</p>
                    </div>
                </div>

                <!-- Estado y Navegación -->
                <div class="flex items-center gap-3">
                    @if($isOnline ?? false)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Motor IA Activo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Motor Offline
                        </span>
                    @endif

                    <a href="{{ url('/') }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-700 hover:text-datamind-primary hover:bg-datamind-neutral transition flex items-center gap-1.5 border border-slate-200">
                        <i class="fa-solid fa-house text-datamind-secondary"></i> Inicio
                    </a>

                    <a href="{{ route('chat.index') }}" class="text-xs font-semibold px-3.5 py-1.5 rounded-lg btn-datamind-primary flex items-center gap-1.5">
                        <i class="fa-solid fa-comments"></i> Ir al Chat AI
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Columna Izquierda (4/12): Formulario de Nueva Conexión y Lista de Conexiones Guardadas -->
        <div class="lg:col-span-4 flex flex-col gap-5">

            <!-- Tarjeta 1: Conectar Nueva Base de Datos -->
            <div class="bg-white rounded-2xl border border-datamind-light/65 p-5 shadow-2xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <h2 class="text-sm font-bold text-datamind-dark flex items-center gap-2">
                        <i class="fa-solid fa-plug-circle-plus text-datamind-primary"></i> Conectar Base de Datos
                    </h2>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded badge-datamind">Solo Lectura</span>
                </div>

                <form id="connectionForm" onsubmit="return false;" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alias / Nombre de la Conexión</label>
                        <input type="text" id="connName" placeholder="Ej: ERP Ventas / Analytics DB" required
                            class="w-full px-3 py-2 rounded-xl border border-datamind-light bg-datamind-neutral/50 text-xs text-slate-800 input-datamind">
                    </div>

                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Motor SQL</label>
                            <select id="connEngine" class="w-full px-3 py-2 rounded-xl border border-datamind-light bg-white text-xs text-slate-800 font-medium input-datamind">
                                <option value="mariadb">MariaDB / MySQL</option>
                                <option value="postgresql">PostgreSQL</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Puerto</label>
                            <input type="number" id="connPort" value="3306" required
                                class="w-full px-3 py-2 rounded-xl border border-datamind-light bg-datamind-neutral/50 text-xs text-slate-800 font-mono input-datamind">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Host / Servidor IP</label>
                            <input type="text" id="connHost" placeholder="127.0.0.1 o db.example.local" value="127.0.0.1" required
                                class="w-full px-3 py-2 rounded-xl border border-datamind-light bg-datamind-neutral/50 text-xs text-slate-800 font-mono input-datamind">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre Base de Datos</label>
                            <input type="text" id="connDatabase" placeholder="Ej: analytics_db" required
                                class="w-full px-3 py-2 rounded-xl border border-datamind-light bg-datamind-neutral/50 text-xs text-slate-800 font-mono input-datamind">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Usuario</label>
                            <input type="text" id="connUsername" placeholder="root / postgres" required
                                class="w-full px-3 py-2 rounded-xl border border-datamind-light bg-datamind-neutral/50 text-xs text-slate-800 input-datamind">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contraseña</label>
                            <input type="password" id="connPassword" placeholder="••••••••"
                                class="w-full px-3 py-2 rounded-xl border border-datamind-light bg-datamind-neutral/50 text-xs text-slate-800 input-datamind">
                        </div>
                    </div>

                    <div id="testResultBanner" class="hidden p-2.5 rounded-xl text-xs border"></div>

                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" id="btnTestConnection"
                            class="flex-1 py-2.5 px-3 rounded-xl btn-datamind-soft text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-bolt"></i> Probar Conexión
                        </button>
                        <button type="button" id="btnSaveConnection"
                            class="flex-1 py-2.5 px-3 rounded-xl btn-datamind-primary text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar y Analizar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tarjeta 2: Conexiones Registradas -->
            <div class="bg-white rounded-2xl border border-datamind-light/65 p-5 shadow-2xs flex-1 flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                    <h2 class="text-sm font-bold text-datamind-dark flex items-center gap-2">
                        <i class="fa-solid fa-server text-datamind-primary"></i> Bases de Datos Guardadas
                    </h2>
                    <span id="savedConnectionsCount" class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-datamind-neutral text-datamind-dark">
                        {{ count($connections ?? []) }}
                    </span>
                </div>

                <div id="connectionsListContainer" class="space-y-2.5 overflow-y-auto custom-scrollbar max-h-96 pr-1">
                    @forelse($connections ?? [] as $conn)
                        <div class="connection-card p-3.5 rounded-xl border transition cursor-pointer {{ !empty($conn['is_active']) ? 'border-datamind-primary bg-datamind-light/15' : 'border-slate-200 hover:border-datamind-light bg-white' }}"
                             data-connection-id="{{ $conn['id'] }}"
                             data-connection-name="{{ $conn['name'] }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-slate-900">{{ $conn['name'] }}</span>
                                        @if(!empty($conn['is_active']))
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-datamind-primary text-white">Activa en Chat</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-slate-500 font-mono mt-0.5">
                                        {{ strtoupper($conn['engine']) }} &bull; {{ $conn['host'] }}:{{ $conn['port'] }}/{{ $conn['database_name'] }}
                                    </p>
                                </div>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded badge-datamind shrink-0">
                                    <i class="fa-solid fa-layer-group text-[10px] mr-1"></i>{{ $conn['synced_tables_count'] ?? 0 }} vectorizadas
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-2 mt-3 pt-2.5 border-t border-slate-100">
                                <button type="button" class="btn-inspect-tables text-[11px] font-semibold text-datamind-primary hover:text-datamind-dark flex items-center gap-1 cursor-pointer"
                                        data-id="{{ $conn['id'] }}" data-name="{{ $conn['name'] }}">
                                    <i class="fa-solid fa-magnifying-glass-chart"></i> Ver Tablas
                                </button>
                                <div class="flex items-center gap-2">
                                    @if(empty($conn['is_active']))
                                        <button type="button" class="btn-activate-conn text-[11px] font-semibold px-2 py-1 rounded bg-datamind-neutral hover:bg-datamind-light/40 text-datamind-dark transition cursor-pointer"
                                                data-id="{{ $conn['id'] }}">
                                            <i class="fa-solid fa-check"></i> Usar en Chat
                                        </button>
                                    @endif
                                    <button type="button" class="btn-delete-conn text-[11px] text-red-500 hover:text-red-700 px-1.5 py-1 cursor-pointer"
                                            data-id="{{ $conn['id'] }}" data-name="{{ $conn['name'] }}" title="Eliminar conexión">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-xs text-slate-400">
                            No hay conexiones registradas todavía. Agrega una arriba para comenzar.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Columna Derecha (8/12): Explorador de Tablas Detectadas y Vectorización Selectiva -->
        <div class="lg:col-span-8 flex flex-col">
            <div class="bg-white rounded-2xl border border-datamind-light/65 shadow-2xs flex-1 flex flex-col overflow-hidden">

                <!-- Cabecera del Explorador de Esquema -->
                <div class="p-5 border-b border-datamind-light/50 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-table-cells text-datamind-primary"></i>
                                <span id="explorerTitle">Explorador de Tablas Detectadas</span>
                            </h2>
                            <span id="explorerStatsBadge" class="hidden text-xs font-semibold px-2.5 py-0.5 rounded-full badge-datamind"></span>
                        </div>
                        <p id="explorerSubtitle" class="text-xs text-slate-500 mt-0.5">
                            Selecciona una conexión a la izquierda o guarda una nueva para detectar todas sus tablas y elegir cuáles enseñarle a la IA (`pgvector`).
                        </p>
                    </div>

                    <!-- Botón Principal de Vectorización -->
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" id="btnVectorizeSelected" disabled
                            class="px-4 py-2.5 rounded-xl btn-datamind-primary text-xs font-semibold flex items-center gap-2 opacity-50 cursor-not-allowed">
                            <i class="fa-solid fa-brain"></i>
                            <span id="btnVectorizeText">Vectorizar Seleccionadas (0)</span>
                        </button>
                    </div>
                </div>

                <!-- Barra de Herramientas de Selección y Filtro -->
                <div id="explorerToolbar" class="hidden px-5 py-3 bg-datamind-neutral/80 border-b border-datamind-light/50 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="button" id="btnSelectAllVisible" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white border border-datamind-light text-datamind-dark hover:bg-datamind-light/30 transition cursor-pointer">
                            <i class="fa-solid fa-check-double mr-1 text-datamind-primary"></i> Seleccionar Visibles
                        </button>
                        <button type="button" id="btnClearSelection" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                            <i class="fa-solid fa-xmark mr-1"></i> Limpiar Selección
                        </button>
                        <button type="button" id="btnSelectUnvectorized" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-datamind-secondary hover:border-datamind-light transition cursor-pointer">
                            <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Solo No Vectorizadas
                        </button>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                        <input type="text" id="tableSearchInput" placeholder="Filtrar tabla o columna..."
                            class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-datamind-light bg-white text-xs text-slate-800 input-datamind">
                    </div>
                </div>

                <!-- Contenedor de la Tabla de Esquemas -->
                <div id="tablesExplorerContainer" class="flex-1 p-5 overflow-y-auto custom-scrollbar">
                    <!-- Placeholder inicial -->
                    <div id="tablesEmptyPlaceholder" class="h-full py-16 flex flex-col items-center justify-center text-center">
                        <div class="h-16 w-16 rounded-2xl badge-datamind flex items-center justify-center text-2xl mb-4">
                            <i class="fa-solid fa-database text-datamind-primary"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 mb-1">Inspección de Esquemas en Vivo</h3>
                        <p class="text-xs text-slate-500 max-w-md">
                            Haz clic en <strong>"Ver Tablas"</strong> en cualquiera de tus bases de datos guardadas o conecta una nueva para explorar todas sus tablas y columnas.
                        </p>
                    </div>

                    <!-- Lista de Tablas Renderizada por JS -->
                    <div id="tablesGridWrapper" class="hidden">
                        <div class="max-h-[480px] overflow-x-auto overflow-y-auto custom-scrollbar rounded-xl border border-datamind-light/60">
                            <table class="w-full text-xs text-left text-slate-700">
                                <thead class="bg-datamind-neutral border-b border-datamind-light/70 text-[11px] font-bold text-datamind-dark uppercase sticky top-0 z-10 shadow-2xs">
                                    <tr>
                                        <th class="py-3 px-4 w-10 text-center bg-datamind-neutral whitespace-nowrap">
                                            <input type="checkbox" id="chkHeaderAll" class="h-4 w-4 rounded accent-[#0476D9] cursor-pointer">
                                        </th>
                                        <th class="py-3 px-4 bg-datamind-neutral whitespace-nowrap">Tabla</th>
                                        <th class="py-3 px-4 text-center bg-datamind-neutral whitespace-nowrap">Columnas</th>
                                        <th class="py-3 px-4 bg-datamind-neutral whitespace-nowrap">Campos Detectados (Vista Previa)</th>
                                        <th class="py-3 px-4 text-center bg-datamind-neutral whitespace-nowrap">Estado en IA</th>
                                    </tr>
                                </thead>
                                <tbody id="tablesListBody" class="divide-y divide-slate-100">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <!-- Scripts Locales Obligatorios (Zero CDNs) -->
    <script src="{{ asset('js/global_js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('js/global_js/all.min.js') }}"></script>
    <script src="{{ asset('js/global_js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/global_js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/global_js/axios.min.js') }}"></script>
    <script src="{{ asset('js/global_js/jquery.dataTables.min.js') }}"></script>

    <!-- Script del Gestor Dinámico de Bases de Datos -->
    <script src="{{ asset('js/connections_manager.js') }}"></script>

</body>
</html>
