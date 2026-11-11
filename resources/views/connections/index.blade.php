<x-app-layout
    title="Gestor de Bases de Datos y Vectorización"
    ecosystem="vectorization"
    ecosystemLabel="Ecosistema de Vectorización & Bases de Datos"
    ecosystemIcon="fa-database"
    activeModule="Gestor de Conexiones & pgvector"
    activeContext="Esquemas de Tablas"
    :isOnline="$isOnline ?? false"
    :fullHeight="true">

    <!-- Layout Monolítico Recto de 2 Columnas Adosadas (0px Border Radius, Sin Espacios Muertos) -->
    <main class="flex-1 w-full flex overflow-hidden rounded-none">

        <!-- Columna Izquierda Adosada: Sidebar Navegable y Acceso Rápido -->
        <aside class="hidden lg:flex flex-col w-84 shrink-0 bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar rounded-none">

            <!-- Sidebar Dinámico del Ecosistema de Vectorización -->
            <x-ecosystem-sidebar ecosystemKey="vectorization" />

            <!-- Resumen Rápido de Bases Guardadas en Sidebar -->
            <div class="p-3 border-b border-slate-200 bg-white">
                <div class="flex items-center justify-between pb-1.5 mb-1.5 border-b border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-server text-datamind-primary"></i> Bases Activas
                    </span>
                    <span id="sidebarConnCountBadge" class="text-[9px] font-bold px-1.5 py-0.2 rounded-none bg-slate-100 text-slate-600 border border-slate-200">
                        {{ count($connections ?? []) }}
                    </span>
                </div>

                <div id="sidebarConnList" class="space-y-1 overflow-y-auto custom-scrollbar max-h-52 pr-0.5">
                    @forelse($connections ?? [] as $conn)
                        <div class="quick-conn-card p-2 border transition cursor-pointer rounded-none {{ !empty($conn['is_active']) ? 'border-datamind-primary bg-slate-50' : 'border-slate-200 hover:border-slate-400 bg-white' }}"
                             data-connection-id="{{ $conn['id'] }}"
                             data-connection-name="{{ $conn['name'] }}">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-xs font-bold text-slate-900 truncate">{{ $conn['name'] }}</span>
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
                        <div class="text-center py-2 text-xs text-slate-400">
                            Sin bases registradas.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Botón Rápido para Crear Conexión -->
            <div class="p-3 bg-slate-50/50 mt-auto">
                <button type="button" id="btnSidebarOpenNewConn"
                        class="w-full py-2 px-3 bg-datamind-primary hover:opacity-95 text-white text-xs font-bold flex items-center justify-center gap-2 cursor-pointer rounded-none transition">
                    <i class="fa-solid fa-plus"></i> Conectar Base de Datos
                </button>
            </div>

        </aside>

        <!-- Columna Derecha Adosada: Contenedor Central con 3 Vistas Monolíticas -->
        <section class="flex-1 flex flex-col bg-white overflow-hidden rounded-none border-0">

            <!-- =========================================================================
                 VISTA 1: FORMULARIO AMPLIO DE NUEVA CONEXIÓN + DESCUBRIMIENTO DE ESQUEMAS
                 ========================================================================= -->
            <div id="panelNewConnection" class="hidden flex-1 flex flex-col overflow-y-auto custom-scrollbar p-6">
                <div class="max-w-4xl w-full mx-auto space-y-5">
                    
                    <!-- Cabecera de la Vista -->
                    <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wide">
                                <i class="fa-solid fa-plug-circle-bolt text-datamind-primary"></i>
                                Nueva Conexión de Base de Datos
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Conecta un motor PostgreSQL o MariaDB/MySQL. Podrás analizar la estructura en vivo y seleccionar qué esquemas deseas incorporar.
                            </p>
                        </div>
                        <button type="button" class="btn-switch-to-explorer px-3 py-1.5 border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-none cursor-pointer">
                            <i class="fa-solid fa-arrow-left mr-1"></i> Volver a Tablas
                        </button>
                    </div>

                    <!-- Formulario de Conexión -->
                    <form id="connectionForm" onsubmit="return false;" class="space-y-4 bg-slate-50/30 p-5 border border-slate-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nombre Descriptivo / Alias</label>
                                <input type="text" id="connName" placeholder="Ej: SUMAIE - DEMO MANRIQUE" required
                                    class="w-full px-3 py-2 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div class="col-span-2">
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Motor de BD</label>
                                    <select id="connEngine" class="w-full px-3 py-2 border border-slate-200 bg-white text-xs text-slate-800 font-semibold rounded-none focus:outline-hidden">
                                        <option value="postgresql">PostgreSQL (Recomendado)</option>
                                        <option value="mariadb">MariaDB / MySQL</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Puerto</label>
                                    <input type="number" id="connPort" value="5432" required
                                        class="w-full px-3 py-2 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Host / Servidor IP</label>
                                <input type="text" id="connHost" placeholder="10.10.12.21" value="127.0.0.1" required
                                    class="w-full px-3 py-2 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nombre de la Base de Datos</label>
                                <input type="text" id="connDatabase" placeholder="demo_manrique" required
                                    class="w-full px-3 py-2 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Usuario</label>
                                <input type="text" id="connUsername" placeholder="postgres" required
                                    class="w-full px-3 py-2 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Contraseña</label>
                                <input type="password" id="connPassword" placeholder="••••••••••••"
                                    class="w-full px-3 py-2 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                            </div>
                        </div>

                        <!-- Banner de Diagnóstico en Tiempo Real -->
                        <div id="testResultBanner" class="hidden p-3.5 text-xs border rounded-none"></div>

                        <!-- Panel de Esquemas Detectados para Selección Previa -->
                        <div id="detectedSchemasContainer" class="hidden p-4 bg-white border border-slate-200 space-y-2.5">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase flex items-center gap-1.5">
                                        <i class="fa-solid fa-layer-group text-datamind-primary"></i>
                                        Esquemas Detectados en la Base de Datos
                                    </h4>
                                    <p class="text-[11px] text-slate-500">
                                        Marca únicamente los esquemas que deseas poner a disposición del asistente y del explorador.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="btnSelectAllSchemas" class="text-[11px] font-semibold px-2 py-0.5 border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer">
                                        Marcar Todos
                                    </button>
                                    <button type="button" id="btnUnselectAllSchemas" class="text-[11px] font-semibold px-2 py-0.5 border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer">
                                        Desmarcar
                                    </button>
                                </div>
                            </div>
                            <div id="schemasCheckboxGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-1 max-h-48 overflow-y-auto custom-scrollbar">
                                <!-- Checkboxes de esquemas inyectados dinámicamente -->
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" id="btnTestConnection"
                                class="py-2 px-4 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-xs font-bold text-slate-800 flex items-center gap-2 cursor-pointer rounded-none transition">
                                <i class="fa-solid fa-bolt"></i> Analizar y Probar BD
                            </button>
                            <button type="button" id="btnSaveConnection"
                                class="py-2 px-5 bg-datamind-primary text-white hover:opacity-95 text-xs font-bold flex items-center gap-2 cursor-pointer rounded-none transition shadow-none">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar Conexión
                            </button>
                        </div>
                    </form>

                </div>
            </div>


            <!-- =========================================================================
                 VISTA 2: PANEL DE BASES GUARDADAS Y GESTIÓN
                 ========================================================================= -->
            <div id="panelSavedConnections" class="hidden flex-1 flex flex-col overflow-y-auto custom-scrollbar p-6">
                <div class="max-w-5xl w-full mx-auto space-y-4">
                    
                    <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wide">
                                <i class="fa-solid fa-server text-datamind-primary"></i>
                                Bases de Datos Registradas
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Administra tus conexiones guardadas, cambia la base activa para el Chat y revisa sus tablas vectorizadas.
                            </p>
                        </div>
                        <button type="button" class="btn-open-new-conn px-3 py-1.5 bg-datamind-primary text-white text-xs font-bold flex items-center gap-1.5 rounded-none cursor-pointer">
                            <i class="fa-solid fa-plus"></i> Conectar Otra Base
                        </button>
                    </div>

                    <div id="savedConnectionsFullGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($connections ?? [] as $conn)
                            <div class="connection-card p-4 border transition rounded-none {{ !empty($conn['is_active']) ? 'border-datamind-primary bg-slate-50/50' : 'border-slate-200 hover:border-slate-400 bg-white' }}"
                                 data-connection-id="{{ $conn['id'] }}"
                                 data-connection-name="{{ $conn['name'] }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-bold text-slate-900 truncate">{{ $conn['name'] }}</h3>
                                            @if(!empty($conn['is_active']))
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-none bg-datamind-primary text-white">Activa en Chat</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500 font-mono mt-1">
                                            {{ strtoupper($conn['engine']) }} &bull; {{ $conn['host'] }}:{{ $conn['port'] }} &bull; <code>{{ $conn['database_name'] }}</code>
                                        </p>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-none bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                                        {{ $conn['synced_tables_count'] ?? 0 }} vectorizadas
                                    </span>
                                </div>

                                <div class="flex items-center justify-between gap-2 mt-4 pt-3 border-t border-slate-100 text-xs">
                                    <button type="button" class="btn-inspect-tables font-bold text-datamind-primary hover:underline flex items-center gap-1.5 cursor-pointer"
                                            data-id="{{ $conn['id'] }}" data-name="{{ $conn['name'] }}">
                                        <i class="fa-solid fa-table-cells"></i> Abrir en Esquemas y Tablas
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="btn-edit-conn text-slate-500 hover:text-datamind-primary p-1 cursor-pointer"
                                                data-id="{{ $conn['id'] }}" data-name="{{ $conn['name'] }}" title="Editar configuración y esquemas">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        @if(empty($conn['is_active']))
                                            <button type="button" class="btn-activate-conn font-semibold px-2.5 py-1 rounded-none bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer border border-slate-200"
                                                    data-id="{{ $conn['id'] }}">
                                                Activar en Chat
                                            </button>
                                        @endif
                                        <button type="button" class="btn-delete-conn text-slate-400 hover:text-red-600 p-1 cursor-pointer"
                                                data-id="{{ $conn['id'] }}" data-name="{{ $conn['name'] }}" title="Eliminar conexión">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-2 text-center py-12 border border-dashed border-slate-200 text-slate-400 text-xs">
                                No tienes ninguna base de datos guardada todavía.
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>


            <!-- =========================================================================
                 VISTA 3: EXPLORADOR DE ESQUEMAS Y TABLAS (DATATABLE ESTÁTICO COMPACTO)
                 ========================================================================= -->
            <div id="panelTablesExplorer" class="flex-1 flex flex-col overflow-hidden">

                <!-- Cabecera Ceñida del Explorador -->
                <div class="px-3.5 py-2.5 border-b border-slate-200 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wide">
                                <i class="fa-solid fa-table-cells text-datamind-primary"></i>
                                <span id="explorerTitle">Explorador de Tablas Detectadas</span>
                            </h2>
                            <span id="explorerStatsBadge" class="hidden text-[10px] font-bold px-2 py-0.5 rounded-none bg-slate-100 text-slate-700 border border-slate-200"></span>
                        </div>
                        <p id="explorerSubtitle" class="text-[11px] text-slate-500 mt-0.5">
                            Selecciona una base para explorar sus tablas, examinar llaves foráneas y vectorizarlas con pgvector.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" id="btnVectorizeSelected" disabled
                            class="px-3 py-1.5 bg-datamind-primary text-white text-xs font-bold flex items-center gap-1.5 opacity-50 cursor-not-allowed rounded-none transition shadow-none">
                            <i class="fa-solid fa-brain"></i>
                            <span id="btnVectorizeText">Vectorizar Seleccionadas (0)</span>
                        </button>
                    </div>
                </div>

                <!-- Barra de Herramientas Compacta (Filtro Multi-Esquema y Búsqueda) -->
                <div id="explorerToolbar" class="hidden px-3.5 py-1.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 shrink-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <!-- Selector de Esquema Dinámico -->
                        <div class="flex items-center gap-1 border border-slate-200 bg-white px-2 py-0.5 rounded-none text-xs">
                            <i class="fa-solid fa-layer-group text-datamind-primary text-[11px]"></i>
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Esquema:</span>
                            <select id="schemaFilterSelect" class="bg-transparent border-0 text-xs font-bold text-slate-800 focus:outline-hidden cursor-pointer py-0.5">
                                <option value="all">Todos los Esquemas</option>
                            </select>
                        </div>

                        <!-- Selector de Estado de Vectorización (Todas / Vectorizadas / No Vectorizadas) -->
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

                    <div class="relative w-full sm:w-60">
                        <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1.5 text-xs text-slate-400"></i>
                        <input type="text" id="tableSearchInput" placeholder="Filtrar tabla o columna..."
                            class="w-full pl-7 pr-2.5 py-0.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                    </div>
                </div>

                <!-- Contenedor del DataTable con Altura Estática Delimitada y Cero Paddings Muertos -->
                <div id="tablesExplorerContainer" class="flex-1 p-2.5 flex flex-col overflow-hidden bg-slate-50/20">
                    <div id="tablesEmptyPlaceholder" class="h-full py-12 flex flex-col items-center justify-center text-center">
                        <div class="h-12 w-12 bg-datamind-neutral border border-slate-200 flex items-center justify-center text-xl mb-3 text-datamind-primary rounded-none">
                            <i class="fa-solid fa-database"></i>
                        </div>
                        <h3 class="text-xs font-bold text-slate-800 mb-1">Inspección de Esquemas y Tablas</h3>
                        <p class="text-[11px] text-slate-500 max-w-sm">
                            Selecciona una base de datos para cargar su diccionario de datos y relaciones JOIN.
                        </p>
                    </div>

                    <div id="tablesGridWrapper" class="hidden flex-1 flex flex-col min-h-0 border border-slate-200 rounded-none bg-white">
                        <div class="flex-1 overflow-auto custom-scrollbar">
                            <table class="w-full text-xs text-left text-slate-700 border-collapse">
                                <thead class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase sticky top-0 z-20 shadow-xs">
                                    <tr>
                                        <th class="py-2 px-3 w-10 text-center bg-slate-50"><input type="checkbox" id="chkHeaderAll" class="h-3.5 w-3.5 rounded-none cursor-pointer"></th>
                                        <th class="py-2 px-3 bg-slate-50">Tabla</th>
                                        <th class="py-2 px-3 text-center bg-slate-50 w-24">Columnas</th>
                                        <th class="py-2 px-3 bg-slate-50">Campos y Llaves Foráneas (JOIN)</th>
                                        <th class="py-2 px-3 text-center bg-slate-50 w-28">Estado IA</th>
                                    </tr>
                                </thead>
                                <tbody id="tablesListBody" class="divide-y divide-slate-100 bg-white">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Barra Inferior Fija de Acciones (Con Botón de Estructura Completa) -->
                <div id="explorerBottomBar" class="px-3.5 py-2 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-slate-500 font-medium">Seleccionadas:</span>
                        <span id="selectedCountBadge" class="font-bold font-mono px-2 py-0.5 rounded-none bg-slate-200 text-slate-800">0 tablas</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- BOTÓN ACTIVADO ESTRICTAMENTE CUANDO SE SELECCIONA 1 TABLA -->
                        <button type="button" id="btnViewTableStructure" disabled
                                class="px-3 py-1.5 bg-slate-200 text-slate-400 text-xs font-bold flex items-center gap-1.5 opacity-50 cursor-not-allowed rounded-none transition border border-slate-300">
                            <i class="fa-solid fa-sitemap"></i> Ver Estructura Completa de la Tabla
                        </button>
                    </div>
                </div>

            </div>

        </section>

    </main>

    <!-- =========================================================================
         MODAL MONOLÍTICO RECTO: ESTRUCTURA COMPLETA DE TABLA
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


    <!-- =========================================================================
         MODAL MONOLÍTICO RECTO: EDITAR CONEXIÓN Y ESQUEMAS
         ========================================================================= -->
    <div id="modalEditConnection" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white border border-slate-300 w-full max-w-3xl max-h-[90vh] flex flex-col rounded-none shadow-2xl">
            <!-- Modal Header -->
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-datamind-primary"></i>
                    <div>
                        <h3 id="modalEditConnTitle" class="text-sm font-bold text-slate-900">Editar Conexión</h3>
                        <p class="text-[11px] text-slate-500">Modifica alias, credenciales y esquemas autorizados</p>
                    </div>
                </div>
                <button type="button" id="btnCloseEditConnModal" class="text-slate-400 hover:text-slate-700 text-base p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body Form -->
            <form id="editConnectionForm" onsubmit="return false;" class="p-5 overflow-y-auto custom-scrollbar space-y-4 text-xs">
                <input type="hidden" id="editConnId">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Nombre / Alias</label>
                        <input type="text" id="editConnName" required
                            class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="block font-bold text-slate-700 uppercase mb-1">Motor</label>
                            <select id="editConnEngine" class="w-full px-2 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-semibold rounded-none focus:outline-hidden">
                                <option value="postgresql">PostgreSQL</option>
                                <option value="mariadb">MariaDB / MySQL</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Puerto</label>
                            <input type="number" id="editConnPort" required
                                class="w-full px-2 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Host IP</label>
                        <input type="text" id="editConnHost" required
                            class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Base de Datos</label>
                        <input type="text" id="editConnDatabase" required
                            class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Usuario</label>
                        <input type="text" id="editConnUsername" required
                            class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Contraseña (Dejar en blanco para no cambiar)</label>
                        <input type="password" id="editConnPassword" placeholder="••••••••••••"
                            class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                    </div>
                </div>

                <!-- Esquemas Habilitados para esta Conexión -->
                <div class="border border-slate-200 p-3 bg-slate-50/50 space-y-2">
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                        <div>
                            <span class="font-bold text-slate-800 uppercase flex items-center gap-1.5">
                                <i class="fa-solid fa-layer-group text-datamind-primary"></i>
                                Esquemas que Deseas Utilizar
                            </span>
                            <p class="text-[10px] text-slate-500">Marca o desmarca los esquemas visibles para el asistente y el explorador.</p>
                        </div>
                        <button type="button" id="btnRefreshEditSchemas" class="px-2 py-1 bg-white border border-slate-200 text-[10px] font-bold text-slate-700 hover:bg-slate-100 cursor-pointer">
                            <i class="fa-solid fa-arrows-rotate mr-1"></i> Detectar Esquemas en Vivo
                        </button>
                    </div>

                    <div id="editSchemasCheckboxGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-1 max-h-44 overflow-y-auto custom-scrollbar">
                        <div class="col-span-full py-4 text-center text-slate-400">
                            Cargando esquemas de la conexión...
                        </div>
                    </div>
                </div>

                <div id="editTestResultBanner" class="hidden p-2.5 text-xs border rounded-none"></div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                    <button type="button" id="btnCancelEditConn" class="px-3.5 py-1.5 bg-white border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-none cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" id="btnSaveEditedConnection" class="px-4 py-1.5 bg-datamind-primary text-white text-xs font-bold hover:opacity-95 rounded-none cursor-pointer">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Actualizar Conexión
                    </button>
                </div>
            </form>
        </div>
    </div>

    <x-slot:scripts>
        <script src="{{ asset('js/connections_manager.js') }}"></script>
    </x-slot:scripts>

</x-app-layout>
