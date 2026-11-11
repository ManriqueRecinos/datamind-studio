<x-app-layout
    title="Bases de Datos Registradas"
    ecosystem="vectorization"
    ecosystemLabel="Ecosistema de Vectorización & Bases de Datos"
    ecosystemIcon="fa-database"
    activeModule="Gestor de Conexiones"
    activeContext="Bases Guardadas"
    :isOnline="$isOnline ?? false"
    :fullHeight="true">

    <!-- Layout Monolítico Recto de 2 Columnas (0px Border Radius, Sin Espacios Muertos) -->
    <main class="flex-1 w-full flex overflow-hidden rounded-none">

        <!-- Columna Izquierda Adosada: Sidebar Navegable -->
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
                        <a href="{{ route('connections.explorer', ['connection_id' => $conn['id']]) }}"
                           class="block p-2 border transition cursor-pointer rounded-none {{ !empty($conn['is_active']) ? 'border-datamind-primary bg-slate-50' : 'border-slate-200 hover:border-slate-400 bg-white' }}">
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
                        </a>
                    @empty
                        <div class="text-center py-2 text-xs text-slate-400">
                            Sin bases registradas.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Botón Rápido para Crear Conexión con Modal -->
            <div class="p-3 bg-slate-50/50 mt-auto">
                <button type="button" id="btnSidebarOpenNewConn"
                        class="w-full py-2 px-3 bg-datamind-primary hover:opacity-95 text-white text-xs font-bold flex items-center justify-center gap-2 cursor-pointer rounded-none transition">
                    <i class="fa-solid fa-plus"></i> Conectar Base de Datos
                </button>
            </div>
        </aside>

        <!-- Columna Derecha: Vista Monolítica de Bases Guardadas -->
        <section class="flex-1 flex flex-col bg-slate-50/20 overflow-y-auto custom-scrollbar p-6">
            <div class="max-w-6xl w-full mx-auto space-y-5">

                <!-- Header de la Vista -->
                <div class="bg-white border border-slate-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="h-8 w-8 bg-datamind-primary/10 text-datamind-primary flex items-center justify-center text-sm font-bold">
                                <i class="fa-solid fa-server"></i>
                            </span>
                            <div>
                                <h1 class="text-base font-bold text-slate-900 uppercase tracking-wide">Bases de Datos Registradas</h1>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Administra tus conexiones, define la base activa para el asistente RAG e inspecciona sus esquemas vectorizados.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" id="btnOpenNewConnModal"
                                class="px-4 py-2 bg-datamind-primary hover:opacity-95 text-white text-xs font-bold flex items-center gap-2 cursor-pointer rounded-none transition shadow-none">
                            <i class="fa-solid fa-plus"></i> Nueva Base de Datos
                        </button>
                    </div>
                </div>

                <!-- Grid de Bases de Datos Guardadas -->
                <div id="savedConnectionsFullGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($connections ?? [] as $conn)
                        <div class="connection-card p-5 border transition rounded-none bg-white {{ !empty($conn['is_active']) ? 'border-datamind-primary ring-1 ring-datamind-primary/20' : 'border-slate-200 hover:border-slate-400' }}"
                             data-connection-id="{{ $conn['id'] }}"
                             data-connection-name="{{ $conn['name'] }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-bold text-slate-900 truncate">{{ $conn['name'] }}</h3>
                                        @if(!empty($conn['is_active']))
                                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-none bg-datamind-primary text-white">Activa en Chat</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-500 font-mono mt-1.5 flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 bg-slate-100 text-slate-700 font-bold uppercase text-[10px]">{{ $conn['engine'] }}</span>
                                        <span>{{ $conn['host'] }}:{{ $conn['port'] }}</span>
                                        <span class="text-slate-300">&bull;</span>
                                        <code class="text-slate-800 font-semibold">{{ $conn['database_name'] }}</code>
                                    </p>
                                </div>
                                <span class="text-[10px] font-bold px-2.5 py-1 rounded-none bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                                    <i class="fa-solid fa-brain text-datamind-primary mr-1"></i> {{ $conn['synced_tables_count'] ?? 0 }} vectorizadas
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-2 mt-5 pt-3.5 border-t border-slate-100 text-xs">
                                <a href="{{ route('connections.explorer', ['connection_id' => $conn['id']]) }}"
                                   class="font-bold text-datamind-primary hover:underline flex items-center gap-1.5">
                                    <i class="fa-solid fa-table-cells"></i> Explorar Esquemas y Tablas &rarr;
                                </a>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="btn-edit-conn text-slate-500 hover:text-datamind-primary p-1.5 cursor-pointer"
                                            data-id="{{ $conn['id'] }}" data-name="{{ $conn['name'] }}" title="Editar configuración y esquemas">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @if(empty($conn['is_active']))
                                        <button type="button" class="btn-activate-conn font-semibold px-2.5 py-1 rounded-none bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer border border-slate-200"
                                                data-id="{{ $conn['id'] }}">
                                            Activar en Chat
                                        </button>
                                    @endif
                                    <button type="button" class="btn-delete-conn text-slate-400 hover:text-red-600 p-1.5 cursor-pointer"
                                            data-id="{{ $conn['id'] }}" data-name="{{ $conn['name'] }}" title="Eliminar conexión">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 text-center py-16 bg-white border border-dashed border-slate-200 text-slate-400 text-xs space-y-3">
                            <i class="fa-solid fa-database text-3xl text-slate-300"></i>
                            <p class="font-medium text-slate-600">No tienes ninguna base de datos guardada todavía.</p>
                            <button type="button" onclick="document.getElementById('btnOpenNewConnModal').click()"
                                    class="px-3 py-1.5 bg-datamind-primary text-white text-xs font-bold inline-flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-plus"></i> Registrar la Primera Base
                            </button>
                        </div>
                    @endforelse
                </div>

            </div>
        </section>

    </main>

    <!-- =========================================================================
         MODAL MONOLÍTICO: REGISTRO DE NUEVA CONEXIÓN DE BASE DE DATOS
         ========================================================================= -->
    <div id="modalNewConnection" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white border border-slate-300 w-full max-w-3xl max-h-[90vh] flex flex-col rounded-none shadow-2xl">
            <!-- Modal Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-plug-circle-bolt text-datamind-primary text-base"></i>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase">Nueva Conexión de Base de Datos</h3>
                        <p class="text-[11px] text-slate-500">Conecta un motor PostgreSQL o MariaDB/MySQL y detecta sus esquemas en vivo.</p>
                    </div>
                </div>
                <button type="button" id="btnCloseNewConnModal" class="text-slate-400 hover:text-slate-700 text-base p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body Form -->
            <form id="connectionForm" onsubmit="return false;" class="p-5 overflow-y-auto custom-scrollbar space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Nombre Descriptivo / Alias</label>
                        <input type="text" id="connName" placeholder="Ej: SUMAIE - DEMO MANRIQUE" required
                            class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="block font-bold text-slate-700 uppercase mb-1">Motor de BD</label>
                            <select id="connEngine" class="w-full px-2 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-semibold rounded-none focus:outline-hidden">
                                <option value="postgresql">PostgreSQL (Recomendado)</option>
                                <option value="mariadb">MariaDB / MySQL</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Puerto</label>
                            <input type="number" id="connPort" value="5432" required
                                class="w-full px-2 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Host / Servidor IP</label>
                        <input type="text" id="connHost" placeholder="10.10.12.21" value="127.0.0.1" required
                            class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Nombre de la Base de Datos</label>
                        <input type="text" id="connDatabase" placeholder="demo_manrique" required
                            class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Usuario</label>
                        <input type="text" id="connUsername" placeholder="postgres" required
                            class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Contraseña</label>
                        <input type="password" id="connPassword" placeholder="••••••••••••"
                            class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                    </div>
                </div>

                <!-- Banner de Diagnóstico en Tiempo Real -->
                <div id="testResultBanner" class="hidden p-3 text-xs border rounded-none"></div>

                <!-- Panel de Esquemas Detectados para Selección Previa -->
                <div id="detectedSchemasContainer" class="hidden p-3.5 bg-slate-50 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                        <div>
                            <span class="font-bold text-slate-800 uppercase flex items-center gap-1.5">
                                <i class="fa-solid fa-layer-group text-datamind-primary"></i>
                                Esquemas Detectados en la Base de Datos
                            </span>
                            <p class="text-[10px] text-slate-500">Marca los esquemas que deseas poner a disposición del explorador y RAG.</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" id="btnSelectAllSchemas" class="text-[10px] font-semibold px-2 py-0.5 border border-slate-200 bg-white hover:bg-slate-100 cursor-pointer">
                                Marcar Todos
                            </button>
                            <button type="button" id="btnUnselectAllSchemas" class="text-[10px] font-semibold px-2 py-0.5 border border-slate-200 bg-white hover:bg-slate-100 cursor-pointer">
                                Desmarcar
                            </button>
                        </div>
                    </div>
                    <div id="schemasCheckboxGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pt-1 max-h-44 overflow-y-auto custom-scrollbar">
                        <!-- Checkboxes de esquemas inyectados dinámicamente -->
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-200">
                    <button type="button" id="btnTestConnection"
                        class="py-2 px-3.5 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-xs font-bold text-slate-800 flex items-center gap-1.5 cursor-pointer rounded-none transition">
                        <i class="fa-solid fa-bolt"></i> Probar Conexión
                    </button>
                    <button type="button" id="btnSaveConnection"
                        class="py-2 px-4 bg-datamind-primary text-white hover:opacity-95 text-xs font-bold flex items-center gap-1.5 cursor-pointer rounded-none transition">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Base
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================================
         MODAL MONOLÍTICO: EDITAR CONEXIÓN Y ESQUEMAS
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
                                class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 font-mono rounded-none focus:outline-hidden">
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

    <!-- Modal de Entrenamiento de la IA -->
    <x-slot:modals>
        @include('chat.partials.training-center-modal')
    </x-slot:modals>

    <x-slot:scripts>
        <script src="{{ asset('js/connections_manager.js') }}"></script>
        <script src="{{ asset('js/training_manager.js') }}"></script>
    </x-slot:scripts>

</x-app-layout>
