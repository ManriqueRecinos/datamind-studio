<x-app-layout
    title="Centro de Aprendizaje de la IA"
    ecosystem="vectorization"
    ecosystemLabel="Vectorización & Bases de Datos"
    ecosystemIcon="fa-database"
    activeModule="Centro de Aprendizaje de la IA"
    activeContext="Reglas en pgvector"
    :isOnline="$isOnline ?? false"
    :fullHeight="true">

    <!-- Layout Monolítico Recto de 2 Columnas Adosadas -->
    <main class="flex-1 w-full flex overflow-hidden rounded-none">

        <!-- Sidebar Izquierdo Adosado Continuo (Piso a Techo) -->
        <aside class="hidden lg:flex flex-col w-80 shrink-0 bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar rounded-none">
            <!-- Módulo Dinámico desde Base de Datos (Ecosistema Vectorización) -->
            <x-ecosystem-sidebar ecosystemKey="vectorization" />

            <!-- Selector de Base de Datos para Contexto de Aprendizaje -->
            <div class="p-3.5 border-b border-slate-200 bg-white">
                <label for="activeConnectionSelect" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1 flex items-center gap-1">
                    <i class="fa-solid fa-database text-datamind-primary text-[10px]"></i> Base de Datos Vinculada
                </label>
                <select id="activeConnectionSelect" class="w-full bg-slate-50 text-xs font-semibold text-slate-800 p-2 border border-slate-200 focus:outline-hidden cursor-pointer rounded-none">
                    @forelse($connections ?? [] as $conn)
                        <option value="{{ $conn['id'] }}" {{ !empty($conn['is_active']) ? 'selected' : '' }}>
                            {{ $conn['name'] }} ({{ strtoupper($conn['engine']) }}: {{ $conn['database_name'] }})
                        </option>
                    @empty
                        <option value="">Base de Datos por Defecto</option>
                    @endforelse
                </select>
            </div>

            <!-- Resumen de Métricas de Aprendizaje -->
            <div class="p-3.5 border-b border-slate-200 bg-slate-50/50">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 mb-2">
                    <i class="fa-solid fa-brain text-emerald-600"></i> Estado de la Memoria
                </span>
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-2 bg-white border border-slate-200">
                        <span class="block text-xs font-bold text-slate-800" id="totalRulesCounter">0</span>
                        <span class="text-[9px] text-slate-500 uppercase font-semibold">Reglas Activas</span>
                    </div>
                    <div class="p-2 bg-white border border-slate-200">
                        <span class="block text-xs font-bold text-emerald-600">768-dim</span>
                        <span class="text-[9px] text-slate-500 uppercase font-semibold">HNSW pgvector</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Panel Central Principal (Piso a Techo) -->
        <section class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden">
            
            <!-- Barra Superior de la Vista (Debajo del Navbar global) con Botón INGEST -->
            <div class="px-5 py-3 border-b border-slate-200 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 bg-datamind-primary text-white flex items-center justify-center font-bold text-base rounded-none shadow-none">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Centro de Aprendizaje & Memoria de la IA</h2>
                        <p class="text-[11px] text-slate-500">Enseña cómo se unen las tablas, qué significan los estados o ingesta documentos corporativos para la IA.</p>
                    </div>
                </div>

                <!-- Botón Ingest y Acciones Superiores -->
                <div class="flex items-center gap-2">
                    <button type="button" id="btnOpenIngestModal"
                            class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider flex items-center gap-1.5 rounded-none cursor-pointer transition shadow-none">
                        <i class="fa-solid fa-file-arrow-up text-xs"></i>
                        <span>Ingest (Subir Archivo)</span>
                    </button>

                    <button type="button" id="btnToggleNewRuleForm"
                            class="px-3.5 py-1.5 bg-datamind-primary hover:opacity-95 text-white font-bold text-xs uppercase tracking-wider flex items-center gap-1.5 rounded-none cursor-pointer transition shadow-none">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Nueva Regla</span>
                    </button>
                </div>
            </div>

            <!-- Contenedor Desplazable con Tabs y Cards -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-5 bg-slate-50/20">

                <!-- Formulario Plegable de Nueva Regla -->
                <div id="newRuleFormContainer" class="hidden bg-white border border-slate-200 p-4.5 rounded-none shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-wand-magic-sparkles text-datamind-primary"></i> Enseñar Nueva Regla o Relación a la IA
                        </h3>
                        <button type="button" id="btnCloseNewRuleForm" class="text-slate-400 hover:text-slate-700 text-xs cursor-pointer p-1">
                            <i class="fa-solid fa-xmark"></i> Ocultar
                        </button>
                    </div>

                    <!-- Selección de Categoría en Tarjetas -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1.5 uppercase">Selecciona el Tipo de Enseñanza:</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                            <button type="button" data-category="custom_relation" class="template-type-btn active p-3 border-2 border-datamind-primary bg-datamind-light/10 text-left transition cursor-pointer rounded-none">
                                <div class="text-xs font-bold text-datamind-dark flex items-center gap-1.5 mb-1">
                                    <i class="fa-solid fa-link text-datamind-primary"></i> Unir 2 Tablas (JOIN)
                                </div>
                                <p class="text-[10px] text-slate-500 leading-tight">Conecta tablas foráneas y muestra el nombre en vez del código ID.</p>
                            </button>
                            <button type="button" data-category="business_rule" class="template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-none">
                                <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                                    <i class="fa-solid fa-tags text-amber-600"></i> Códigos y Estados
                                </div>
                                <p class="text-[10px] text-slate-500 leading-tight">Explica qué significa un número o letra (ej: 'A' = Activo, '1' = En Proceso).</p>
                            </button>
                            <button type="button" data-category="sql_example" class="template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-none">
                                <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                                    <i class="fa-solid fa-code text-indigo-600"></i> Consulta SQL Ejemplo
                                </div>
                                <p class="text-[10px] text-slate-500 leading-tight">Pega un SELECT de oro verificado para que la IA lo use de plantilla.</p>
                            </button>
                            <button type="button" data-category="kpi_formula" class="template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-none">
                                <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                                    <i class="fa-solid fa-calculator text-emerald-600"></i> Fórmula o Cálculo
                                </div>
                                <p class="text-[10px] text-slate-500 leading-tight">Enseña cómo se calcula un indicador o valor matemático.</p>
                            </button>
                        </div>
                    </div>

                    <form id="formTrainKnowledgeView" onsubmit="return false;" class="space-y-3">
                        <input type="hidden" id="trainCategoryInput" value="custom_relation">

                        <!-- Campos Asistidos para Unión JOIN -->
                        <div id="customRelationFields" class="space-y-2.5 p-3 bg-slate-50 border border-slate-200 rounded-none">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800 border-b border-slate-200 pb-1.5">
                                <i class="fa-solid fa-diagram-project text-datamind-primary"></i>
                                <span>Parámetros de Relación Foránea</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 text-xs">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1 text-[11px]">1. Tabla Origen</label>
                                    <input type="text" id="trainSourceTable" placeholder="ej: users o role_user"
                                           class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs font-mono rounded-none focus:outline-hidden">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1 text-[11px]">2. Columna Foránea</label>
                                    <input type="text" id="trainSourceCol" placeholder="ej: user_id o role_id"
                                           class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs font-mono rounded-none focus:outline-hidden">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1 text-[11px]">3. Tabla Maestra</label>
                                    <input type="text" id="trainTargetTable" placeholder="ej: roles o companies"
                                           class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs font-mono rounded-none focus:outline-hidden">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1 text-[11px]">4. Campo Legible</label>
                                    <input type="text" id="trainLabelCol" placeholder="name o nombre"
                                           class="w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs font-mono rounded-none focus:outline-hidden">
                                </div>
                            </div>
                        </div>

                        <!-- Campo para SQL Ejemplo -->
                        <div id="sqlExampleFieldBox" class="hidden space-y-1.5 p-3 bg-slate-900 text-slate-100 rounded-none">
                            <div class="flex items-center justify-between text-xs font-bold text-slate-300">
                                <span><i class="fa-solid fa-terminal text-emerald-400 mr-1.5"></i> Consulta SQL `SELECT` Verificada</span>
                                <span class="text-[10px] font-normal text-slate-400">PostgreSQL / MariaDB estándar</span>
                            </div>
                            <textarea id="trainSqlQueryInput" rows="3" placeholder="SELECT u.name AS usuario, r.name AS rol FROM users u JOIN role_user ru ON u.id = ru.user_id JOIN roles r ON ru.role_id = r.id LIMIT 20;"
                                      class="w-full px-3 py-2 border border-slate-700 bg-slate-950 text-emerald-300 font-mono text-xs rounded-none focus:border-emerald-500 focus:outline-hidden custom-scrollbar"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Título de la Regla</label>
                            <input type="text" id="trainTitleInput" placeholder="Ej: Relación de usuarios y roles mediante role_user"
                                   class="w-full px-3 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Instrucción en Lenguaje Natural</label>
                            <textarea id="trainContentInput" rows="3" placeholder="Ej: Une la tabla users con roles por medio de role_user..."
                                      class="w-full px-3 py-2 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden custom-scrollbar"></textarea>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                            <button type="button" id="btnFillExampleTemplate" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer rounded-none transition flex items-center gap-1.5">
                                <i class="fa-solid fa-wand-magic text-datamind-primary"></i> Cargar Plantilla de Ejemplo
                            </button>
                            <button type="button" id="btnSaveTrainingRule" class="px-4 py-2 bg-datamind-primary text-white hover:opacity-95 text-xs font-bold cursor-pointer rounded-none shadow-none transition flex items-center gap-2">
                                <i class="fa-solid fa-brain"></i> Guardar y Vectorizar en pgvector
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Barra de Filtrado de Cards de Aprendizaje -->
                <div class="flex flex-wrap items-center justify-between gap-3 pb-2 border-b border-slate-200">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-layer-group text-datamind-primary"></i> Galería de Aprendizajes
                        </span>
                        <div class="flex items-center gap-1 border border-slate-200 bg-white px-2 py-0.5 rounded-none text-xs">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Categoría:</span>
                            <select id="filterCategorySelect" class="bg-transparent border-0 text-xs font-bold text-slate-800 focus:outline-hidden cursor-pointer py-0.5">
                                <option value="all">Todas las Categorías</option>
                                <option value="custom_relation">Relaciones JOIN</option>
                                <option value="business_rule">Códigos & Estados</option>
                                <option value="sql_example">Ejemplos SQL</option>
                                <option value="kpi_formula">Fórmulas & KPIs</option>
                                <option value="process_manual">Documentos Ingestados</option>
                            </select>
                        </div>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-xs text-slate-400"></i>
                        <input type="text" id="searchRulesInput" placeholder="Buscar regla, tabla o concepto..."
                               class="w-full pl-7 pr-2.5 py-1 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                    </div>
                </div>

                <!-- Grid de Tarjetas (Cards) de Aprendizajes -->
                <div id="rulesCardsGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5">
                    <div class="col-span-full py-12 text-center text-slate-400 text-xs">
                        <i class="fa-solid fa-circle-notch fa-spin text-xl text-datamind-primary mb-2"></i>
                        <p>Cargando cápsulas de aprendizaje desde pgvector...</p>
                    </div>
                </div>

            </div>

        </section>

    </main>

    <!-- Modal Detallado de Inspección de Aprendizaje -->
    <div id="modalRuleDetail" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white border border-slate-200 rounded-none shadow-xl flex flex-col max-h-[90vh]">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span id="detailCategoryBadge" class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 text-slate-800 border border-slate-200 rounded-none">
                        Categoría
                    </span>
                    <h4 id="detailRuleTitle" class="text-xs font-bold text-slate-800 truncate">Detalle de la Regla</h4>
                </div>
                <button type="button" id="btnCloseRuleDetail" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div class="p-4 overflow-y-auto custom-scrollbar space-y-3.5 text-xs">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Instrucción y Contenido Completo</label>
                    <div id="detailRuleContent" class="p-3 bg-slate-50 border border-slate-200 text-slate-700 whitespace-pre-line font-mono text-[11px]"></div>
                </div>

                <div id="detailSqlWrapper" class="hidden space-y-1">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sentencia SQL Asociada</label>
                    <pre id="detailRuleSql" class="p-3 bg-slate-900 text-emerald-300 font-mono text-[11px] overflow-x-auto border border-slate-700 custom-scrollbar"></pre>
                </div>

                <div id="detailMetaWrapper" class="space-y-1">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Metadatos de Vectorización</label>
                    <div id="detailRuleMeta" class="p-2.5 bg-slate-50 border border-slate-200 text-slate-500 font-mono text-[10px] grid grid-cols-2 gap-2"></div>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <button type="button" id="btnDeleteCurrentRule" class="px-3 py-1.5 bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold cursor-pointer rounded-none transition flex items-center gap-1.5">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                    <span>Eliminar de pgvector</span>
                </button>
                <button type="button" id="btnCloseDetailFooter" class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-semibold cursor-pointer rounded-none transition">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Superior para INGEST (Subida de Archivos PDF, Excel, MD, Word) -->
    <div id="modalIngestFile" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div class="relative w-full max-w-xl bg-white border border-slate-200 rounded-none shadow-xl flex flex-col">
            <div class="px-4 py-3 bg-emerald-50 border-b border-emerald-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-6 w-6 bg-emerald-600 text-white flex items-center justify-center text-xs font-bold rounded-none">
                        <i class="fa-solid fa-file-arrow-up"></i>
                    </span>
                    <h4 class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Ingest: Vectorizar Documento Corporativo</h4>
                </div>
                <button type="button" id="btnCloseIngestModal" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formIngestFile" class="p-4 space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Título del Documento / Proceso</label>
                    <input type="text" id="ingestTitleInput" placeholder="Ej: Manual de Procedimientos y Políticas de Inventario"
                           class="w-full px-3 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Departamento / Área Responsable</label>
                    <input type="text" id="ingestDeptInput" value="Operaciones / Producción" placeholder="Ej: Calidad, Ingeniería, Auditoría"
                           class="w-full px-3 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
                </div>

                <!-- Zona Drag & Drop para Archivos -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Seleccionar Archivo (.pdf, .xlsx, .xls, .md, .docx, .txt)</label>
                    <div id="dropZoneContainer" class="border-2 border-dashed border-slate-300 hover:border-emerald-500 p-6 text-center bg-slate-50 hover:bg-emerald-50/20 cursor-pointer transition">
                        <i class="fa-solid fa-cloud-arrow-up text-2xl text-emerald-600 mb-2"></i>
                        <p class="text-xs font-bold text-slate-700 mb-0.5">Haz clic para seleccionar o arrastra un archivo aquí</p>
                        <p class="text-[10px] text-slate-500">PDF, Excel (.xlsx, .xls), Markdown (.md), Word (.docx), Texto (.txt) hasta 20 MB</p>
                        <input type="file" id="ingestFileInput" class="hidden" accept=".pdf,.xlsx,.xls,.md,.txt,.docx,.csv,.tsv">
                    </div>
                    <div id="selectedFileInfo" class="hidden mt-2 p-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-semibold flex items-center justify-between">
                        <span id="selectedFileName"><i class="fa-solid fa-file mr-1"></i> Archivo.pdf</span>
                        <button type="button" id="btnClearSelectedFile" class="text-emerald-700 hover:text-red-600 cursor-pointer p-0.5">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div id="ingestProgressBanner" class="hidden p-3 bg-slate-50 border border-slate-200 text-[11px] text-slate-600">
                    <div class="flex items-center gap-2 mb-1.5">
                        <i class="fa-solid fa-circle-notch fa-spin text-emerald-600"></i>
                        <span class="font-bold text-slate-800">Particionando y calculando embeddings nomic-embed-text...</span>
                    </div>
                    <p class="text-[10px] text-slate-500">Este proceso ejecuta chunking recursivo y almacena los vectores en PostgreSQL 17 con índice HNSW.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                    <button type="button" id="btnCancelIngest" class="px-3.5 py-1.5 bg-white border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-none cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" id="btnSubmitIngest" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-none cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Vectorizar Documento</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <x-slot:scripts>
        <script src="{{ asset('js/training_view.js') }}"></script>
    </x-slot:scripts>

</x-app-layout>
