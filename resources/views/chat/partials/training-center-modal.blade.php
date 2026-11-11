<x-modal id="modalTrainingCenter" title="Centro de Aprendizaje de la IA" icon="fa-graduation-cap" size="max-w-4xl">
    <x-slot:subtitle>
        Configura fácilmente cómo se relacionan tus tablas, qué significan los estados o sube documentos para que la IA nunca invente datos.
    </x-slot:subtitle>

    <!-- Pestañas de Navegación -->
    <div class="flex border-b border-slate-200 mb-4 bg-slate-50">
        <button type="button" class="training-tab-btn active px-4 py-2.5 text-xs font-bold border-b-2 border-datamind-primary text-datamind-primary bg-white cursor-pointer rounded-none" data-target="tabAddRule">
            <i class="fa-solid fa-wand-magic-sparkles mr-1.5 text-datamind-primary"></i> 1. Enseñar a la IA
        </button>
        <button type="button" class="training-tab-btn px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900 cursor-pointer rounded-none" data-target="tabLearnedList">
            <i class="fa-solid fa-brain mr-1.5 text-emerald-600"></i> 2. Reglas Aprendidas (<span id="learnedRulesCount">0</span>)
        </button>
        <button type="button" class="training-tab-btn px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900 cursor-pointer rounded-none" data-target="tabCurriculum">
            <i class="fa-solid fa-circle-question mr-1.5 text-amber-500"></i> 3. Ejemplos Frecuentes
        </button>
    </div>

    <!-- PESTAÑA 1: Enseñar Nueva Regla (Diseño Asistido Simple) -->
    <div id="tabAddRule" class="training-tab-pane space-y-4">
        
        <!-- Selección de Acción en Tarjetas Grandes -->
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">¿Qué deseas enseñarle a la IA?</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                <button type="button" data-category="custom_relation" class="template-type-btn active p-3 border-2 border-datamind-primary bg-datamind-light/10 text-left transition cursor-pointer rounded-lg hover:shadow-xs">
                    <div class="text-xs font-bold text-datamind-dark flex items-center gap-1.5 mb-1">
                        <i class="fa-solid fa-link text-datamind-primary text-sm"></i> Unir 2 Tablas (JOIN)
                    </div>
                    <p class="text-[11px] text-slate-500">Conecta una tabla con otra y muestra el nombre en vez del código ID.</p>
                </button>
                <button type="button" data-category="business_rule" class="template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-lg hover:shadow-xs">
                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                        <i class="fa-solid fa-tags text-amber-600 text-sm"></i> Códigos y Estados
                    </div>
                    <p class="text-[11px] text-slate-500">Explica qué significa un número o letra (ej: 'A' = Activo, '1' = En Proceso).</p>
                </button>
                <button type="button" data-category="sql_example" class="template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-lg hover:shadow-xs">
                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                        <i class="fa-solid fa-code text-indigo-600 text-sm"></i> Consulta SQL Ejemplo
                    </div>
                    <p class="text-[11px] text-slate-500">Pega un SELECT que funcione bien para que la IA lo use de plantilla.</p>
                </button>
                <button type="button" data-category="kpi_formula" class="template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-lg hover:shadow-xs">
                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                        <i class="fa-solid fa-calculator text-emerald-600 text-sm"></i> Fórmula o Cálculo
                    </div>
                    <p class="text-[11px] text-slate-500">Enseña cómo se calcula un indicador o valor matemático.</p>
                </button>
            </div>
        </div>

        <form id="formTrainKnowledge" onsubmit="return false;" class="space-y-4 bg-white p-4 border border-slate-200 rounded-xl shadow-2xs">
            <input type="hidden" id="trainCategoryInput" value="custom_relation">

            <!-- BLOQUE ASISTIDO 1: Relación entre Tablas (Simple y visual) -->
            <div id="customRelationFields" class="space-y-3 p-3 bg-slate-50/80 border border-slate-200 rounded-lg">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-800 border-b border-slate-200 pb-2">
                    <i class="fa-solid fa-diagram-project text-datamind-primary"></i>
                    <span>Configuración Asistida de Unión de Tablas</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">1. Tabla con los datos (Tabla Origen)</label>
                        <input type="text" id="trainSourceTable" placeholder="ej: inventario_partes o users" 
                               class="w-full px-3 py-2 border border-slate-200 bg-white text-xs font-mono rounded-md focus:border-datamind-primary focus:outline-hidden">
                        <span class="text-[10px] text-slate-400">La tabla donde está el código o la clave foránea.</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">2. Campo con el código foráneo (FK)</label>
                        <input type="text" id="trainSourceCol" placeholder="ej: id_parte_maquina o current_company_id" 
                               class="w-full px-3 py-2 border border-slate-200 bg-white text-xs font-mono rounded-md focus:border-datamind-primary focus:outline-hidden">
                        <span class="text-[10px] text-slate-400">La columna numérica que apunta a la otra tabla.</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">3. Tabla que tiene el nombre real (Tabla Maestra)</label>
                        <input type="text" id="trainTargetTable" placeholder="ej: hop.parte_maquina o companies" 
                               class="w-full px-3 py-2 border border-slate-200 bg-white text-xs font-mono rounded-md focus:border-datamind-primary focus:outline-hidden">
                        <span class="text-[10px] text-slate-400">La tabla catálogo donde reside el nombre legible.</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">4. Clave Primaria (PK) y Campo a Mostrar</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="trainTargetCol" value="id" placeholder="id" title="Clave Primaria Maestra"
                                   class="px-2.5 py-2 border border-slate-200 bg-white text-xs font-mono rounded-md focus:border-datamind-primary focus:outline-hidden">
                            <input type="text" id="trainLabelCol" placeholder="nombre o name" title="Columna con el nombre real"
                                   class="px-2.5 py-2 border border-datamind-primary/60 bg-emerald-50/50 text-xs font-mono font-semibold text-slate-800 rounded-md focus:border-datamind-primary focus:outline-hidden">
                        </div>
                        <span class="text-[10px] text-slate-400">Izquierda: PK (generalmente <code>id</code>). Derecha: campo legible a mostrar.</span>
                    </div>
                </div>
            </div>

            <!-- BLOQUE ASISTIDO 2: SQL Example -->
            <div id="sqlExampleFieldBox" class="hidden space-y-1.5 p-3 bg-slate-900 rounded-lg text-slate-100">
                <div class="flex items-center justify-between text-xs font-bold text-slate-300 mb-1">
                    <span><i class="fa-solid fa-terminal text-emerald-400 mr-1.5"></i> Consulta SQL `SELECT` Verificada</span>
                    <span class="text-[10px] font-normal text-slate-400">Usa sintaxis PostgreSQL estándar</span>
                </div>
                <textarea id="trainSqlQueryInput" rows="3" placeholder="SELECT p.numero_item, pm.nombre, st.existencia_actual FROM inventario_partes p JOIN hop.parte_maquina pm ON p.id_parte_maquina = pm.id_parte_maquina ..."
                          class="w-full px-3 py-2 border border-slate-700 bg-slate-950 text-emerald-300 font-mono text-xs rounded-md focus:border-emerald-500 focus:outline-hidden"></textarea>
            </div>

            <!-- Título e Instrucción en Lenguaje Natural -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Título corto de la regla</label>
                <input type="text" id="trainTitleInput" placeholder="Ej: Relación de repuestos inventario con catálogo HOP"
                       class="w-full px-3 py-2 border border-slate-200 bg-white text-xs text-slate-800 rounded-md focus:border-datamind-primary focus:outline-hidden">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-700">Instrucción en Lenguaje Natural para el Asistente</label>
                    <span class="text-[11px] text-slate-400">Explícalo como si le hablaras a un compañero de trabajo</span>
                </div>
                <textarea id="trainContentInput" rows="3"
                          placeholder="Ej: Cuando consulten repuestos, une siempre inventario_partes con hop.parte_maquina usando id_parte_maquina y muestra el nombre y numero_parte del catálogo en lugar del código numérico."
                          class="w-full px-3 py-2 border border-slate-200 bg-white text-xs text-slate-800 rounded-md focus:border-datamind-primary focus:outline-hidden"></textarea>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <button type="button" id="btnFillExampleTemplate" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer rounded-md transition flex items-center gap-1.5">
                    <i class="fa-solid fa-wand-magic text-datamind-primary"></i> Cargar Plantilla de Ejemplo
                </button>
                <button type="button" id="btnSaveTrainingRule" class="px-4 py-2 bg-datamind-primary text-white hover:opacity-95 text-xs font-bold cursor-pointer rounded-md shadow-xs transition flex items-center gap-2">
                    <i class="fa-solid fa-brain"></i> Guardar y Vectorizar Conocimiento
                </button>
            </div>
        </form>
    </div>

    <!-- PESTAÑA 2: Conocimiento Aprendido -->
    <div id="tabLearnedList" class="training-tab-pane hidden space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-xs text-slate-600">Reglas, uniones `JOIN`, fórmulas y ejemplos almacenados permanentemente en `pgvector`:</p>
            <button type="button" id="btnRefreshRulesList" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs font-semibold text-slate-700 cursor-pointer rounded-md transition">
                <i class="fa-solid fa-rotate mr-1"></i> Actualizar
            </button>
        </div>
        <div id="learnedRulesContainer" class="space-y-2">
            <div class="text-center py-8 text-xs text-slate-400">Cargando conocimiento...</div>
        </div>
    </div>

    <!-- PESTAÑA 3: Ejemplos Frecuentes -->
    <div id="tabCurriculum" class="training-tab-pane hidden space-y-3 text-xs">
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-lg">
            <strong class="font-bold flex items-center gap-1.5 mb-0.5">
                <i class="fa-solid fa-lightbulb text-emerald-600"></i> ¿Cómo aprende mejor la IA?
            </strong>
            <p class="text-[11px] text-emerald-800">
                La IA funciona como un analista nuevo en la empresa: dale reglas concisas y directas. Aquí tienes las 3 fórmulas más efectivas:
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="p-3 border border-slate-200 bg-white rounded-lg shadow-2xs space-y-1">
                <span class="font-bold text-datamind-primary block">1. Uniones de Tablas</span>
                <p class="text-slate-600 text-[11px]">Indica qué tabla se une con cuál y qué columna legible debe mostrar en lugar del número ID.</p>
                <div class="p-2 bg-slate-50 text-[10px] font-mono text-slate-700 rounded-sm">
                    "Une inventario_partes con hop.parte_maquina por id_parte_maquina y muestra pm.nombre"
                </div>
            </div>
            <div class="p-3 border border-slate-200 bg-white rounded-lg shadow-2xs space-y-1">
                <span class="font-bold text-amber-600 block">2. Estados de Negocio</span>
                <p class="text-slate-600 text-[11px]">Evita que filtre a ciegas diciéndole qué letra o código representa qué condición.</p>
                <div class="p-2 bg-slate-50 text-[10px] font-mono text-slate-700 rounded-sm">
                    "En inventario_partes, es_usado = TRUE significa que el repuesto es reacondicionado"
                </div>
            </div>
            <div class="p-3 border border-slate-200 bg-white rounded-lg shadow-2xs space-y-1">
                <span class="font-bold text-indigo-600 block">3. Consultas de Oro</span>
                <p class="text-slate-600 text-[11px]">Si ya tienes un SQL que saca los datos perfectos, guárdalo aquí como plantilla fija.</p>
                <div class="p-2 bg-slate-50 text-[10px] font-mono text-slate-700 rounded-sm">
                    "SELECT estilo, operacion, aguja FROM ... LIMIT 50"
                </div>
            </div>
        </div>
    </div>
</x-modal>