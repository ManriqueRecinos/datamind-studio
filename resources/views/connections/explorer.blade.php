<x-app-layout
    title="Esquemas y Tablas - pgvector"
    ecosystem="vectorization"
    ecosystemLabel="Ecosistema de Vectorización & Bases de Datos"
    ecosystemIcon="fa-database"
    activeModule="Explorador y pgvector"
    activeContext="Esquemas y Tablas"
    :isOnline="$isOnline ?? false"
    :fullHeight="true">

    <!-- Layout Monolítico Recto de 2 Columnas (0px Border Radius, Sin Espacios Muertos) -->
    <main class="flex-1 w-full flex overflow-hidden rounded-none">

        <!-- Columna Izquierda Adosada: Sidebar Navegable -->
        <aside class="hidden lg:flex flex-col w-84 shrink-0 bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar rounded-none">
            <!-- Sidebar Dinámico del Ecosistema de Vectorización -->
            <x-ecosystem-sidebar ecosystemKey="vectorization" />

            <!-- Selector Rápido de Base de Datos para Explorar -->
            <div class="p-3 border-b border-slate-200 bg-white">
                <div class="flex items-center justify-between pb-1.5 mb-1.5 border-b border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-server text-datamind-primary"></i> Seleccionar Base
                    </span>
                    <span id="sidebarConnCountBadge" class="text-[9px] font-bold px-1.5 py-0.2 rounded-none bg-slate-100 text-slate-600 border border-slate-200">
                        {{ count($connections ?? []) }}
                    </span>
                </div>

                <div id="sidebarConnList" class="space-y-1 overflow-y-auto custom-scrollbar max-h-60 pr-0.5">
                    @forelse($connections ?? [] as $conn)
                        @php
                            $isSelected = isset($activeConnection['id']) && $activeConnection['id'] == $conn['id'];
                        @endphp
                        <div class="quick-conn-card p-2 border transition cursor-pointer rounded-none {{ $isSelected ? 'border-datamind-primary bg-slate-50 font-bold' : 'border-slate-200 hover:border-slate-400 bg-white' }}"
                             data-connection-id="{{ $conn['id'] }}"
                             data-connection-name="{{ $conn['name'] }}">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-xs text-slate-900 truncate">{{ $conn['name'] }}</span>
                                @if(!empty($conn['is_active']))
                                    <span class="text-[8px] font-bold px-1 py-0.2 rounded-none bg-datamind-primary text-white">Activa</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between mt-1 text-[10px] text-slate-500 font-mono">
                                <span>{{ strtoupper($conn['engine']) }} &bull; {{ $conn['database_name'] }}</span>
                                <span class="text-datamind-primary font-bold">{{ $conn['synced_tables_count'] ?? 0 }} vect.</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-3 text-xs text-slate-400">
                            Sin bases registradas.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Acceso a Bases Guardadas -->
            <div class="p-3 bg-slate-50/50 mt-auto border-t border-slate-200">
                <a href="{{ route('connections.index') }}"
                   class="w-full py-2 px-3 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center gap-2 cursor-pointer rounded-none transition">
                    <i class="fa-solid fa-server"></i> Administrar Bases Guardadas
                </a>
            </div>
        </aside>

        <!-- Columna Derecha: Explorador Dedicado de Tablas y Esquemas -->
        <section class="flex-1 flex flex-col bg-white overflow-hidden rounded-none border-0">

            <!-- Cabecera Ceñida del Explorador -->
            <div class="px-4 py-3 border-b border-slate-200 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wide">
                            <i class="fa-solid fa-table-cells text-datamind-primary"></i>
                            <span id="explorerTitle">{{ $activeConnection['name'] ?? 'Explorador de Tablas Detectadas' }}</span>
                        </h2>
                        <span id="explorerStatsBadge" class="hidden text-[10px] font-bold px-2 py-0.5 rounded-none bg-slate-100 text-slate-700 border border-slate-200"></span>
                    </div>
                    <p id="explorerSubtitle" class="text-[11px] text-slate-500 mt-0.5">
                        Inspecciona tablas, columnas, relaciones JOIN foráneas y vectoriza sus esquemas con pgvector para el RAG.
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" id="btnVectorizeSelected" disabled
                        class="px-3.5 py-1.5 bg-datamind-primary text-white text-xs font-bold flex items-center gap-1.5 opacity-50 cursor-not-allowed rounded-none transition shadow-none">
                        <i class="fa-solid fa-brain"></i>
                        <span id="btnVectorizeText">Vectorizar Seleccionadas (0)</span>
                    </button>
                </div>
            </div>

            <!-- Barra de Herramientas Compacta (Filtros de Esquema, Estado IA y Buscador) -->
            <div id="explorerToolbar" class="px-4 py-2 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Selector de Conexión en Barra (en caso de pantallas pequeñas o cambio rápido) -->
                    <div class="flex items-center gap-1 border border-slate-200 bg-white px-2 py-0.5 rounded-none text-xs">
                        <i class="fa-solid fa-database text-datamind-primary text-[11px]"></i>
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Base:</span>
                        <select id="connSelectorSelect" class="bg-transparent border-0 text-xs font-bold text-slate-800 focus:outline-hidden cursor-pointer py-0.5">
                            @foreach($connections ?? [] as $conn)
                                <option value="{{ $conn['id'] }}" data-name="{{ $conn['name'] }}" {{ (isset($activeConnection['id']) && $activeConnection['id'] == $conn['id']) ? 'selected' : '' }}>
                                    {{ $conn['name'] }} ({{ strtoupper($conn['engine']) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Selector de Esquema Dinámico -->
                    <div class="flex items-center gap-1 border border-slate-200 bg-white px-2 py-0.5 rounded-none text-xs">
                        <i class="fa-solid fa-layer-group text-datamind-primary text-[11px]"></i>
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Esquema:</span>
                        <select id="schemaFilterSelect" class="bg-transparent border-0 text-xs font-bold text-slate-800 focus:outline-hidden cursor-pointer py-0.5">
                            <option value="all">Todos los Esquemas</option>
                        </select>
                    </div>

                    <!-- Selector de Estado de Vectorización -->
                    <div class="flex items-center gap-1 border border-slate-200 bg-white px-2 py-0.5 rounded-none text-xs">
                        <i class="fa-solid fa-wand-magic-sparkles text-amber-500 text-[11px]"></i>
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Estado IA:</span>
                        <select id="vectorFilterSelect" class="bg-transparent border-0 text-xs font-bold text-slate-800 focus:outline-hidden cursor-pointer py-0.5">
                            <option value="all">Todas las Tablas</option>
                            <option value="vectorized">Solo Vectorizadas</option>
                            <option value="unvectorized">Solo No Vectorizadas</option>
                        </select>
                    </div>

                    <button type="button" id="btnSelectAllVisible" class="text-xs font-semibold px-2 py-0.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition cursor-pointer rounded-none">
                        <i class="fa-solid fa-check-double mr-1 text-datamind-primary"></i> Visibles
                    </button>
                    <button type="button" id="btnClearSelection" class="text-xs font-semibold px-2 py-0.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 transition cursor-pointer rounded-none">
                        <i class="fa-solid fa-xmark mr-1"></i> Limpiar
                    </button>
                    <button type="button" id="btnSelectUnvectorized" class="text-xs font-semibold px-2 py-0.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition cursor-pointer rounded-none">
                        <i class="fa-solid fa-wand-magic-sparkles mr-1 text-amber-500"></i> No Vectorizadas
                    </button>
                </div>

                <div class="relative w-full sm:w-64">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-xs text-slate-400"></i>
                    <input type="text" id="tableSearchInput" placeholder="Filtrar tabla o columna..."
                        class="w-full pl-7 pr-2.5 py-1 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                </div>
            </div>

            <!-- Contenedor del DataTable -->
            <div id="tablesExplorerContainer" class="flex-1 p-3 flex flex-col overflow-hidden bg-slate-50/20">
                <div id="tablesEmptyPlaceholder" class="h-full py-12 flex flex-col items-center justify-center text-center">
                    <div class="h-12 w-12 bg-white border border-slate-200 flex items-center justify-center text-xl mb-3 text-datamind-primary rounded-none">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 mb-1">Cargando Tablas de la Base...</h3>
                    <p class="text-[11px] text-slate-500 max-w-sm">
                        Obteniendo el diccionario de datos, llaves foráneas y estado de embeddings.
                    </p>
                </div>

                <div id="tablesGridWrapper" class="hidden flex-1 flex flex-col min-h-0 border border-slate-200 rounded-none bg-white">
                    <div class="flex-1 overflow-auto custom-scrollbar">
                        <table class="w-full text-xs text-left text-slate-700 border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase sticky top-0 z-20 shadow-xs">
                                <tr>
                                    <th class="py-2.5 px-3 w-10 text-center bg-slate-50"><input type="checkbox" id="chkHeaderAll" class="h-3.5 w-3.5 rounded-none cursor-pointer"></th>
                                    <th class="py-2.5 px-3 bg-slate-50">Tabla</th>
                                    <th class="py-2.5 px-3 text-center bg-slate-50 w-24">Columnas</th>
                                    <th class="py-2.5 px-3 bg-slate-50">Campos y Llaves Foráneas (JOIN)</th>
                                    <th class="py-2.5 px-3 text-center bg-slate-50 w-28">Estado IA</th>
                                </tr>
                            </thead>
                            <tbody id="tablesListBody" class="divide-y divide-slate-100 bg-white">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Barra Inferior Fija de Acciones -->
            <div id="explorerBottomBar" class="px-4 py-2 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-slate-500 font-medium">Seleccionadas:</span>
                    <span id="selectedCountBadge" class="font-bold font-mono px-2 py-0.5 rounded-none bg-slate-200 text-slate-800">0 tablas</span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="btnViewTableStructure" disabled
                            class="px-3 py-1.5 bg-slate-200 text-slate-400 text-xs font-bold flex items-center gap-1.5 opacity-50 cursor-not-allowed rounded-none transition border border-slate-300">
                        <i class="fa-solid fa-sitemap"></i> Ver Estructura Completa de la Tabla
                    </button>
                </div>
            </div>

        </section>

    </main>

    <!-- =========================================================================
         MODAL MONOLÍTICO: ESTRUCTURA COMPLETA DE TABLA
         ========================================================================= -->
    <div id="modalTableStructure" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white border border-slate-300 w-full max-w-4xl max-h-[85vh] flex flex-col rounded-none shadow-2xl">
            <!-- Modal Header -->
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-table text-datamind-primary"></i>
                    <div>
                        <h3 id="modalTableTitle" class="text-xs sm:text-sm font-bold text-slate-900 font-mono">public.users</h3>
                        <p id="modalTableSubtitle" class="text-[10px] text-slate-500">Diccionario de Datos & Relaciones</p>
                    </div>
                </div>
                <button type="button" id="btnCloseTableStructureModal" class="text-slate-400 hover:text-slate-700 text-base p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Tabs -->
            <div class="flex border-b border-slate-200 px-4 bg-slate-50/50 text-xs shrink-0">
                <button type="button" class="tab-structure-btn py-2 px-3 font-bold border-b-2 border-datamind-primary text-datamind-primary cursor-pointer" data-tab="tabColumns">
                    <i class="fa-solid fa-columns mr-1"></i> Columnas (<span id="modalColCount">0</span>)
                </button>
                <button type="button" class="tab-structure-btn py-2 px-3 font-medium text-slate-500 hover:text-slate-800 cursor-pointer" data-tab="tabForeignKeys">
                    <i class="fa-solid fa-diagram-project mr-1"></i> Llaves Foráneas (<span id="modalFkCount">0</span>)
                </button>
                <button type="button" class="tab-structure-btn py-2 px-3 font-medium text-slate-500 hover:text-slate-800 cursor-pointer" data-tab="tabIndexes">
                    <i class="fa-solid fa-bolt mr-1"></i> Índices (<span id="modalIdxCount">0</span>)
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 flex-1 overflow-y-auto custom-scrollbar text-xs">
                <!-- Tab: Columnas -->
                <div id="tabColumns" class="tab-structure-content space-y-2">
                    <div class="overflow-x-auto border border-slate-200">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase font-bold text-slate-500">
                                <tr>
                                    <th class="py-2 px-3 bg-slate-50">Columna</th>
                                    <th class="py-2 px-3 bg-slate-50">Tipo</th>
                                    <th class="py-2 px-3 bg-slate-50 text-center">Nulo</th>
                                    <th class="py-2 px-3 bg-slate-50">Comentario / Descripción</th>
                                </tr>
                            </thead>
                            <tbody id="modalColumnsBody" class="divide-y divide-slate-100 bg-white font-mono text-[11px]">
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Llaves Foráneas -->
                <div id="tabForeignKeys" class="tab-structure-content hidden space-y-2">
                    <div id="modalFkEmpty" class="py-8 text-center text-slate-400 text-xs">No tiene llaves foráneas salientes o entrantes registradas.</div>
                    <div id="modalFkWrapper" class="hidden overflow-x-auto border border-slate-200">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase font-bold text-slate-500">
                                <tr>
                                    <th class="py-2 px-3 bg-slate-50">Columna Origen</th>
                                    <th class="py-2 px-3 bg-slate-50">Tabla Destino</th>
                                    <th class="py-2 px-3 bg-slate-50">Columna Destino</th>
                                    <th class="py-2 px-3 bg-slate-50">Campos Descriptivos (JOIN)</th>
                                </tr>
                            </thead>
                            <tbody id="modalFkBody" class="divide-y divide-slate-100 bg-white font-mono text-[11px]">
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Índices -->
                <div id="tabIndexes" class="tab-structure-content hidden space-y-2">
                    <div id="modalIdxEmpty" class="py-8 text-center text-slate-400 text-xs">No se detectaron índices secundarios adicionales.</div>
                    <div id="modalIdxWrapper" class="hidden overflow-x-auto border border-slate-200">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase font-bold text-slate-500">
                                <tr>
                                    <th class="py-2 px-3 bg-slate-50">Nombre del Índice</th>
                                    <th class="py-2 px-3 bg-slate-50">Definición DDL</th>
                                </tr>
                            </thead>
                            <tbody id="modalIdxBody" class="divide-y divide-slate-100 bg-white font-mono text-[11px]">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 border-t border-slate-200 bg-slate-50 flex items-center justify-end shrink-0">
                <button type="button" id="btnDismissModal" class="px-3 py-1.5 bg-white border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-none cursor-pointer">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Entrenamiento de la IA -->
    <x-slot:modals>
        @include('chat.partials.training-center-modal')
    </x-slot:modals>

    <x-slot:scripts>
        <script src="{{ asset('js/connections_manager.js') }}"></script>
        <script src="{{ asset('js/training_manager.js') }}"></script>
    </x-slot:scripts>

</x-app-layout>
