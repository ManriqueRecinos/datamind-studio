<x-modal id="modalTrainingCenter" title="Centro de Entrenamiento IA (RAG & Text-to-SQL)" icon="fa-graduation-cap" size="max-w-4xl">
    <x-slot:subtitle>
        Enseña relaciones, reglas de negocio, fórmulas y ejemplos SQL para que la IA nunca invente datos
    </x-slot:subtitle>

    <!-- Pestañas de Navegación del Modal -->
    <div class="flex border-b border-slate-200 mb-4 bg-slate-50">
        <button type="button" class="training-tab-btn active px-4 py-2 text-xs font-bold border-b-2 border-datamind-primary text-datamind-primary bg-white cursor-pointer rounded-none" data-target="tabAddRule">
            <i class="fa-solid fa-plus-circle mr-1"></i> 1. Enseñar Nueva Regla
        </button>
        <button type="button" class="training-tab-btn px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-900 cursor-pointer rounded-none" data-target="tabLearnedList">
            <i class="fa-solid fa-list-check mr-1"></i> 2. Conocimiento Aprendido (<span id="learnedRulesCount">0</span>)
        </button>
        <button type="button" class="training-tab-btn px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-900 cursor-pointer rounded-none" data-target="tabCurriculum">
            <i class="fa-solid fa-graduation-cap mr-1"></i> 3. Currículo Práctico (5 Niveles)
        </button>
    </div>

    <!-- PESTAÑA 1: Enseñar Nueva Regla -->
    <div id="tabAddRule" class="training-tab-pane space-y-4">
        <p class="text-xs text-slate-600">
            Selecciona el tipo de conocimiento que deseas transmitirle a la IA para esta base de datos:
        </p>

        <!-- 4 Plantillas Guiadas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
            <button type="button" data-category="custom_relation" class="template-type-btn active p-3 border border-datamind-primary bg-datamind-neutral text-left transition cursor-pointer rounded-none">
                <div class="text-xs font-bold text-datamind-dark flex items-center gap-1.5 mb-1">
                    <i class="fa-solid fa-link text-datamind-primary"></i> 1. Relación entre Tablas
                </div>
                <p class="text-[11px] text-slate-500">Unir tabla A con tabla B y mostrar el nombre real en vez del ID</p>
            </button>
            <button type="button" data-category="business_rule" class="template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-none">
                <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                    <i class="fa-solid fa-book-open text-datamind-primary"></i> 2. Estados y Sinónimos
                </div>
                <p class="text-[11px] text-slate-500">Explicar qué significa estado=1, 2 o códigos de negocio</p>
            </button>
            <button type="button" data-category="kpi_formula" class="template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-none">
                <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                    <i class="fa-solid fa-calculator text-datamind-primary"></i> 3. Fórmula / Indicador
                </div>
                <p class="text-[11px] text-slate-500">Enseñar cómo se calcula Peso Neto, Merma, Eficiencia</p>
            </button>
            <button type="button" data-category="sql_example" class="template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-none">
                <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                    <i class="fa-solid fa-code text-datamind-primary"></i> 4. Ejemplo SQL de Oro
                </div>
                <p class="text-[11px] text-slate-500">Darle una consulta SELECT ... JOIN perfecta como ejemplo</p>
            </button>
        </div>

        <form id="formTrainKnowledge" onsubmit="return false;" class="space-y-3 bg-slate-50/70 p-4 border border-slate-200 rounded-none">
            <input type="hidden" id="trainCategoryInput" value="custom_relation">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Título de la enseñanza</label>
                <input type="text" id="trainTitleInput" placeholder="Ej: Relación users con companies por current_company_id"
                       class="w-full px-3 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden">
            </div>

            <!-- Campos guiados para custom_relation -->
            <div id="customRelationFields" class="grid grid-cols-1 sm:grid-cols-5 gap-2 p-2.5 bg-white border border-slate-200 rounded-none">
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Tabla Hija</label>
                    <input type="text" id="trainSourceTable" placeholder="users" class="w-full px-2 py-1 border border-slate-200 text-xs font-mono rounded-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Columna Foránea</label>
                    <input type="text" id="trainSourceCol" placeholder="current_company_id" class="w-full px-2 py-1 border border-slate-200 text-xs font-mono rounded-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Tabla Maestra</label>
                    <input type="text" id="trainTargetTable" placeholder="companies" class="w-full px-2 py-1 border border-slate-200 text-xs font-mono rounded-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-600 mb-0.5">PK Maestra</label>
                    <input type="text" id="trainTargetCol" value="id" class="w-full px-2 py-1 border border-slate-200 text-xs font-mono rounded-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-datamind-primary mb-0.5">Columna Nombre Real</label>
                    <input type="text" id="trainLabelCol" placeholder="name" class="w-full px-2 py-1 border border-datamind-light bg-slate-50 text-xs font-mono rounded-none">
                </div>
            </div>

            <!-- Campo SQL para sql_example -->
            <div id="sqlExampleFieldBox" class="hidden">
                <label class="block text-xs font-bold text-slate-700 mb-1">Consulta SQL `SELECT` Verificada</label>
                <textarea id="trainSqlQueryInput" rows="3" placeholder="SELECT c.name AS empresa, COUNT(u.id) AS total FROM users u JOIN companies c ON u.current_company_id = c.id GROUP BY c.name"
                          class="w-full px-3 py-1.5 border border-slate-200 bg-slate-900 text-slate-100 font-mono text-xs rounded-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Instrucción en lenguaje natural para la IA</label>
                <textarea id="trainContentInput" rows="3"
                          placeholder="Ej: La tabla users se relaciona con companies uniendo users.current_company_id = companies.id. Cuando el usuario pida datos por empresa, nunca muestres current_company_id; haz siempre JOIN companies y muestra companies.name."
                          class="w-full px-3 py-1.5 border border-slate-200 bg-white text-xs text-slate-800 rounded-none focus:outline-hidden"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" id="btnFillExampleTemplate" class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-semibold cursor-pointer rounded-none">
                    <i class="fa-solid fa-flask text-datamind-primary mr-1"></i> Cargar Ejemplo
                </button>
                <button type="button" id="btnSaveTrainingRule" class="px-4 py-1.5 bg-datamind-primary text-white hover:opacity-95 text-xs font-bold cursor-pointer rounded-none flex items-center gap-1.5">
                    <i class="fa-solid fa-brain"></i> Vectorizar y Enseñar
                </button>
            </div>
        </form>
    </div>

    <!-- PESTAÑA 2: Lista de Conocimiento Aprendido -->
    <div id="tabLearnedList" class="training-tab-pane hidden space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-xs text-slate-600">Reglas, uniones `JOIN`, fórmulas y ejemplos SQL almacenados en `pgvector`:</p>
            <button type="button" id="btnRefreshRulesList" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs font-semibold text-slate-700 cursor-pointer rounded-none">
                <i class="fa-solid fa-rotate mr-1"></i> Actualizar
            </button>
        </div>
        <div id="learnedRulesContainer" class="space-y-1.5">
            <div class="text-center py-8 text-xs text-slate-400">Cargando conocimiento...</div>
        </div>
    </div>

    <!-- PESTAÑA 3: Currículo Práctico de 5 Niveles -->
    <div id="tabCurriculum" class="training-tab-pane hidden space-y-2.5 text-xs">
        <div class="p-3 bg-slate-50 border border-slate-200 text-slate-700">
            <strong class="text-datamind-dark">🎓 Guía de Maestría RAG:</strong> Sigue estos 5 pasos para entrenar a la perfección a tu IA:
        </div>
        <div class="space-y-2">
            <div class="p-3 border border-slate-200 bg-white">
                <span class="font-bold text-datamind-primary">Nivel 1 — Vectorizar Parejas Maestra + Detalle juntas:</span>
                <p class="text-slate-600 mt-0.5">En <strong>Bases de Datos</strong> (`/connections`), selecciona siempre la tabla hija junto con su tabla maestra en el mismo lote.</p>
            </div>
            <div class="p-3 border border-slate-200 bg-white">
                <span class="font-bold text-datamind-primary">Nivel 2 — Enseñar Relaciones con Nombres Especiales (`JOIN`):</span>
                <p class="text-slate-600 mt-0.5">Si una columna se llama `current_company_id`, indica qué columna de la tabla maestra tiene el nombre real (`name`).</p>
            </div>
            <div class="p-3 border border-slate-200 bg-white">
                <span class="font-bold text-datamind-primary">Nivel 3 — Enseñar Diccionario de Estados y Sinónimos:</span>
                <p class="text-slate-600 mt-0.5">Explica qué significa `estado = 1` o `2` para que los gráficos muestren nombres legibles.</p>
            </div>
            <div class="p-3 border border-slate-200 bg-white">
                <span class="font-bold text-datamind-primary">Nivel 4 — Enseñar Fórmulas de Negocio:</span>
                <p class="text-slate-600 mt-0.5">Registra cómo se calculan indicadores como `Peso Neto = peso_bruto - peso_tara`.</p>
            </div>
            <div class="p-3 border border-slate-200 bg-white">
                <span class="font-bold text-datamind-primary">Nivel 5 — Guardar Consultas SQL de Oro (Few-Shot):</span>
                <p class="text-slate-600 mt-0.5">Usa el botón <strong>"🧠 Enseñar este SQL a la IA"</strong> debajo de cualquier respuesta exitosa en el chat.</p>
            </div>
        </div>
    </div>
</x-modal>
