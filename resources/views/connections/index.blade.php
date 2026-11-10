<x-app-layout
    title="Gestor de Bases de Datos y Vectorización"
    ecosystem="vectorization"
    ecosystemLabel="Ecosistema de Vectorización & Bases de Datos"
    ecosystemIcon="fa-database"
    activeModule="Gestor de Conexiones & pgvector"
    activeContext="Esquemas de Tablas"
    :isOnline="$isOnline ?? false"
    :fullHeight="true">

    <!-- Layout Monolítico Recto de 2 Columnas Adosadas (Sin Espacios Muertos, Sin Bordes Redondeados) -->
    <main class="flex-1 w-full flex overflow-hidden rounded-none">

        <!-- Columna Izquierda Adosada: Sidebar y Gestor de Bases (Piso a Techo) -->
        <aside class="hidden lg:flex flex-col w-84 shrink-0 bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar rounded-none">

            <!-- Sidebar Dinámico del Ecosistema de Vectorización desde Base de Datos -->
            <x-ecosystem-sidebar ecosystemKey="vectorization" />

            <!-- Bases de Datos Guardadas y Estado Rápido -->
            <div id="connectionsContainer" class="p-3.5 border-b border-slate-200 bg-white">
                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                    <h2 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-server text-datamind-primary"></i> Bases Guardadas
                    </h2>
                    <span id="savedConnectionsCount" class="text-[9px] font-bold px-1.5 py-0.2 rounded-none bg-slate-100 text-slate-600 border border-slate-200">
                        {{ count($connections ?? []) }}
                    </span>
                </div>

                <div id="connectionsListContainer" class="space-y-1.5 overflow-y-auto custom-scrollbar max-h-60 pr-1">
                    @forelse($connections ?? [] as $conn)
                        <div class="connection-card p-2.5 border transition cursor-pointer rounded-none {{ !empty($conn['is_active']) ? 'border-datamind-primary bg-slate-50' : 'border-slate-200 hover:border-slate-400 bg-white' }}"
                             data-connection-id="{{ $conn['id'] }}"
                             data-connection-name="{{ $conn['name'] }}">
                            <div class="flex items-start justify-between gap-1.5">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-slate-900 truncate">{{ $conn['name'] }}</span>
                                        @if(!empty($conn['is_active']))
                                            <span class="text-[9px] font-bold px-1 py-0.2 rounded-none bg-datamind-primary text-white">Activa</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-mono truncate mt-0.5">
                                        {{ strtoupper($conn['engine']) }} &bull; {{ $conn['database_name'] }}
                                    </p>
                                </div>
                                <span class="text-[9px] font-semibold px-1 py-0.2 rounded-none bg-slate-100 text-slate-600 border border-slate-200 shrink-0">
                                    {{ $conn['synced_tables_count'] ?? 0 }} vect.
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-2 mt-2 pt-2 border-t border-slate-100 text-[11px]">
                                <button type="button" class="btn-inspect-tables font-semibold text-datamind-primary hover:underline flex items-center gap-1 cursor-pointer"
                                        data-id="{{ $conn['id'] }}" data-name="{{ $conn['name'] }}">
                                    <i class="fa-solid fa-magnifying-glass-chart"></i> Explorar
                                </button>
                                <div class="flex items-center gap-1.5">
                                    @if(empty($conn['is_active']))
                                        <button type="button" class="btn-activate-conn font-semibold px-2 py-0.5 rounded-none bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer"
                                                data-id="{{ $conn['id'] }}" title="Activar para consultas en Chat">
                                            Usar
                                        </button>
                                    @endif
                                    <button type="button" class="btn-delete-conn text-slate-400 hover:text-red-600 p-1 cursor-pointer"
                                            data-id="{{ $conn['id'] }}" data-name="{{ $conn['name'] }}" title="Eliminar conexión">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-xs text-slate-400">
                            No hay conexiones registradas.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Botón Colapsable Recto para Nueva Conexión -->
            <div class="p-3.5 bg-slate-50/50">
                <button type="button" id="btnToggleNewConnForm"
                        onclick="$('#newConnCollapsible').slideToggle(200);"
                        class="w-full py-2 px-3 bg-datamind-primary hover:opacity-95 text-white text-xs font-bold flex items-center justify-center gap-2 cursor-pointer rounded-none">
                    <i class="fa-solid fa-plus"></i> Conectar Base de Datos
                </button>

                <div id="newConnCollapsible" class="hidden mt-3 pt-3 border-t border-slate-200">
                    <form id="connectionForm" onsubmit="return false;" class="space-y-2.5">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Nombre / Alias</label>
                            <input type="text" id="connName" placeholder="Ej: ERP Ventas" required
                                class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div class="col-span-2">
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Motor</label>
                                <select id="connEngine" class="w-full px-2 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-medium rounded-none focus:outline-hidden">
                                    <option value="mariadb">MariaDB / MySQL</option>
                                    <option value="postgresql">PostgreSQL</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Puerto</label>
                                <input type="number" id="connPort" value="3306" required
                                    class="w-full px-2 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Host IP</label>
                                <input type="text" id="connHost" placeholder="127.0.0.1" value="127.0.0.1" required
                                    class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Base de Datos</label>
                                <input type="text" id="connDatabase" placeholder="erp_db" required
                                    class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Usuario</label>
                                <input type="text" id="connUsername" placeholder="root" required
                                    class="w-full px-2 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Contraseña</label>
                                <input type="password" id="connPassword" placeholder="••••••••"
                                    class="w-full px-2 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                            </div>
                        </div>

                        <div id="testResultBanner" class="hidden p-2 text-xs border rounded-none"></div>

                        <div class="flex items-center gap-2 pt-1">
                            <button type="button" id="btnTestConnection"
                                class="flex-1 py-1.5 px-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-xs font-semibold flex items-center justify-center gap-1 cursor-pointer rounded-none">
                                <i class="fa-solid fa-bolt"></i> Test
                            </button>
                            <button type="button" id="btnSaveConnection"
                                class="flex-1 py-1.5 px-2 bg-datamind-primary text-white hover:opacity-95 text-xs font-semibold flex items-center justify-center gap-1 cursor-pointer rounded-none">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </aside>

        <!-- Columna Derecha Adosada: Explorador Recto de Tablas -->
        <section class="flex-1 flex flex-col bg-white overflow-hidden rounded-none border-0">

            <!-- Cabecera del Explorador de Esquema -->
            <div class="p-4 border-b border-slate-200 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wide">
                            <i class="fa-solid fa-table-cells text-datamind-primary"></i>
                            <span id="explorerTitle">Explorador de Tablas Detectadas</span>
                        </h2>
                        <span id="explorerStatsBadge" class="hidden text-[10px] font-bold px-2 py-0.5 rounded-none bg-slate-100 text-slate-700 border border-slate-200"></span>
                    </div>
                    <p id="explorerSubtitle" class="text-xs text-slate-500 mt-0.5">
                        Selecciona una base de datos o guarda una nueva para explorar sus tablas y vectorizarlas con pgvector.
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" id="btnVectorizeSelected" disabled
                        class="px-4 py-2 bg-datamind-primary text-white text-xs font-bold flex items-center gap-2 opacity-50 cursor-not-allowed rounded-none transition">
                        <i class="fa-solid fa-brain"></i>
                        <span id="btnVectorizeText">Vectorizar Seleccionadas (0)</span>
                    </button>
                </div>
            </div>

            <!-- Barra de Herramientas de Selección y Filtro Multi-Esquema -->
            <div id="explorerToolbar" class="hidden px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <!-- Selector de Esquema Dinámico -->
                    <div class="flex items-center gap-1 border border-slate-200 bg-white px-2 py-1 rounded-none text-xs">
                        <i class="fa-solid fa-layer-group text-datamind-primary text-[11px]"></i>
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Esquema:</span>
                        <select id="schemaFilterSelect" class="bg-transparent border-0 text-xs font-bold text-slate-800 focus:outline-hidden cursor-pointer">
                            <option value="all">Todos los Esquemas</option>
                        </select>
                    </div>

                    <button type="button" id="btnSelectAllVisible" class="text-xs font-semibold px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition cursor-pointer rounded-none">
                        <i class="fa-solid fa-check-double mr-1 text-datamind-primary"></i> Seleccionar Visibles
                    </button>
                    <button type="button" id="btnClearSelection" class="text-xs font-semibold px-2.5 py-1 bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 transition cursor-pointer rounded-none">
                        <i class="fa-solid fa-xmark mr-1"></i> Limpiar
                    </button>
                    <button type="button" id="btnSelectUnvectorized" class="text-xs font-semibold px-2.5 py-1 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition cursor-pointer rounded-none">
                        <i class="fa-solid fa-wand-magic-sparkles mr-1 text-amber-500"></i> Solo No Vectorizadas
                    </button>
                </div>

                <div class="relative w-full sm:w-64">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2 text-xs text-slate-400"></i>
                    <input type="text" id="tableSearchInput" placeholder="Filtrar tabla, esquema o columna..."
                        class="w-full pl-8 pr-3 py-1 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                </div>
            </div>

            <!-- Contenedor de la Tabla de Esquemas -->
            <div id="tablesExplorerContainer" class="flex-1 p-5 overflow-y-auto custom-scrollbar">
                <div id="tablesEmptyPlaceholder" class="h-full py-16 flex flex-col items-center justify-center text-center">
                    <div class="h-14 w-14 bg-datamind-neutral border border-slate-200 flex items-center justify-center text-2xl mb-4 text-datamind-primary rounded-none">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800 mb-1">Inspección de Esquemas en Vivo</h3>
                    <p class="text-xs text-slate-500 max-w-md">
                        Haz clic en <strong>"Explorar"</strong> en cualquiera de tus bases de datos guardadas para examinar sus campos y relaciones `JOIN`.
                    </p>
                </div>

                <div id="tablesGridWrapper" class="hidden">
                    <div class="overflow-x-auto border border-slate-200 rounded-none">
                        <table class="w-full text-xs text-left text-slate-700">
                            <thead class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase sticky top-0 z-10">
                                <tr>
                                    <th class="py-2.5 px-3 w-10 text-center bg-slate-50"><input type="checkbox" id="chkHeaderAll" class="h-3.5 w-3.5 rounded-none cursor-pointer"></th>
                                    <th class="py-2.5 px-3 bg-slate-50">Tabla</th>
                                    <th class="py-2.5 px-3 text-center bg-slate-50">Columnas</th>
                                    <th class="py-2.5 px-3 bg-slate-50">Campos y Llaves Foráneas (JOIN)</th>
                                    <th class="py-2.5 px-3 text-center bg-slate-50">Estado IA</th>
                                </tr>
                            </thead>
                            <tbody id="tablesListBody" class="divide-y divide-slate-100 bg-white">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </section>

    </main>

    <x-slot:scripts>
        <script src="{{ asset('js/connections_manager.js') }}"></script>
    </x-slot:scripts>

</x-app-layout>
