/**
 * DataMind AI - QueryBuilder: Constructor visual interactivo y bidireccional de consultas compuestas complejas.
 *
 * Características:
 *  - Estado reactivo en memoria: tabla base, lista de JOINs (INNER, LEFT, RIGHT, FULL, CROSS),
 *    proyecciones SELECT (columnas, alias, agregaciones), filtros WHERE, GROUP BY, ORDER BY, LIMIT y UNIONs.
 *  - Integración nativa con TablePicker para paginar tablas vectorizadas de 10 en 10.
 *  - Selectores dinámicos de columnas para ON, WHERE, SELECT y ORDER BY cargados vía /training/vectorized-tables/columns.
 *  - Sincronización bidireccional:
 *      * Modo Visual -> Compilación SQL en vivo con debounce (300ms) llamando a /training/query-blueprint/compile.
 *      * Modo Script SQL -> Parseo de SQL complejo a Blueprint con /training/query-blueprint/parse.
 *  - Soporte de raw_blocks para preservar fragmentos avanzados no mapeables (CTEs WITH, subconsultas).
 *  - Botón "Probar (LIMIT 20)" para ejecutar de forma segura y renderizar la muestra en tabla.
 *  - Validación semántica contra FastAPI /training/query-blueprint/validate.
 */

(function (window) {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    class QueryBuilder {
        constructor(options) {
            this.container = options.container;
            this.getConnectionId = options.getConnectionId;
            this.onSqlChange = options.onSqlChange || function () {};
            this.notify = options.notify || function (msg, type) { alert(msg); };

            // Estado del blueprint
            this.state = {
                base: { table: '', alias: 't1' },
                joins: [],
                select: [],
                where: [],
                group_by: [],
                order_by: [],
                limit: 20,
                unions: [],
                raw_blocks: []
            };

            this.columnsCache = new Map(); // table_name -> columns_array
            this.activeTab = 'visual'; // 'visual' | 'sql'
            this.compileDebounceTimer = null;
            this.isCompiling = false;

            this.renderLayout();
            this.bindEvents();
        }

        renderLayout() {
            this.container.innerHTML = `
                <div class="qb-root border border-slate-300 bg-white rounded-none shadow-xs text-xs space-y-0">
                    <!-- Barra de Pestañas: Constructor Visual / Script SQL -->
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-100 px-3 py-1.5">
                        <div class="flex items-center gap-1">
                            <button type="button" class="qb-tab-btn qb-tab-visual active px-3 py-1 text-xs font-bold bg-white text-datamind-primary border-t-2 border-datamind-primary rounded-none cursor-pointer">
                                <i class="fa-solid fa-diagram-project mr-1"></i> Constructor Visual
                            </button>
                            <button type="button" class="qb-tab-btn qb-tab-sql px-3 py-1 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-200/60 rounded-none cursor-pointer">
                                <i class="fa-solid fa-code mr-1"></i> Script SQL (Editor)
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" class="qb-btn-reset px-2.5 py-1 text-[11px] font-semibold text-slate-600 hover:text-red-600 bg-white border border-slate-200 rounded-none cursor-pointer">
                                <i class="fa-solid fa-rotate-left mr-1"></i> Limpiar
                            </button>
                            <button type="button" class="qb-btn-dry-run px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-none cursor-pointer shadow-none">
                                <i class="fa-solid fa-play mr-1"></i> Probar (LIMIT 20)
                            </button>
                        </div>
                    </div>

                    <!-- Contenedor del Constructor Visual -->
                    <div class="qb-visual-panel p-4 space-y-4">
                        
                        <!-- 1. Tabla Base FROM -->
                        <div class="p-3 bg-slate-50 border border-slate-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                    <span class="w-4 h-4 bg-datamind-primary text-white text-[10px] flex items-center justify-center font-bold">1</span>
                                    Tabla Principal (FROM)
                                </span>
                                <span class="text-[10px] text-slate-400">Punto de partida de la consulta</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Tabla vectorizada:</label>
                                    <div class="qb-base-table-picker" data-table-picker data-placeholder="Seleccionar tabla base...">
                                        <input type="hidden" class="qb-base-table-val">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Alias:</label>
                                    <input type="text" class="qb-base-alias w-full px-2.5 py-1.5 border border-slate-200 bg-white font-mono text-xs rounded-none" value="t1" placeholder="t1">
                                </div>
                            </div>
                        </div>

                        <!-- 2. Lista de Tablas Unificadas (JOINs) -->
                        <div class="p-3 bg-slate-50 border border-slate-200 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                    <span class="w-4 h-4 bg-indigo-600 text-white text-[10px] flex items-center justify-center font-bold">2</span>
                                    Tablas Unificadas (JOINs)
                                </span>
                                <button type="button" class="qb-btn-add-join px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold rounded-none cursor-pointer">
                                    <i class="fa-solid fa-plus mr-1"></i> Agregar Unión
                                </button>
                            </div>
                            <div class="qb-joins-container space-y-2">
                                <div class="qb-joins-empty text-slate-400 text-xs italic py-2 text-center border border-dashed border-slate-200 bg-white">
                                    No hay uniones añadidas. Haz clic en "Agregar Unión" para vincular otra tabla (INNER, LEFT, RIGHT, FULL o CROSS).
                                </div>
                            </div>
                        </div>

                        <!-- 3. Columnas Proyectadas (SELECT) -->
                        <div class="p-3 bg-slate-50 border border-slate-200 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                    <span class="w-4 h-4 bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">3</span>
                                    Columnas Proyectadas (SELECT)
                                </span>
                                <button type="button" class="qb-btn-add-select px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold rounded-none cursor-pointer">
                                    <i class="fa-solid fa-plus mr-1"></i> Agregar Columna
                                </button>
                            </div>
                            <div class="qb-selects-container space-y-1.5">
                                <div class="qb-selects-empty text-slate-400 text-xs italic py-2 text-center border border-dashed border-slate-200 bg-white">
                                    Proyectando <code class="font-mono bg-slate-100 px-1">*</code> por defecto. Agrega columnas específicas si deseas limitar los campos o aplicar agregaciones (COUNT, SUM, AVG, etc.).
                                </div>
                            </div>
                        </div>

                        <!-- 4. Filtros (WHERE), Orden (ORDER BY) y Límite -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                            <!-- Filtros WHERE -->
                            <div class="p-3 bg-slate-50 border border-slate-200 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                        <i class="fa-solid fa-filter text-amber-600"></i> Filtros (WHERE)
                                    </span>
                                    <button type="button" class="qb-btn-add-where px-2 py-0.5 bg-amber-600 hover:bg-amber-700 text-white text-[10px] font-bold rounded-none cursor-pointer">
                                        <i class="fa-solid fa-plus mr-0.5"></i> Filtro
                                    </button>
                                </div>
                                <div class="qb-wheres-container space-y-1.5">
                                    <div class="qb-wheres-empty text-slate-400 text-[11px] italic py-1.5 text-center border border-dashed border-slate-200 bg-white">
                                        Sin filtros WHERE activos.
                                    </div>
                                </div>
                            </div>

                            <!-- Ordenamiento y Límite -->
                            <div class="p-3 bg-slate-50 border border-slate-200 space-y-2">
                                <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                    <i class="fa-solid fa-arrow-down-wide-short text-slate-700"></i> Orden y Límite
                                </span>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-2">
                                        <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Ordenar por:</label>
                                        <div class="flex gap-1">
                                            <select class="qb-order-col w-full px-2 py-1 border border-slate-200 bg-white text-xs font-mono rounded-none">
                                                <option value="">(Sin orden específico)</option>
                                            </select>
                                            <select class="qb-order-dir px-2 py-1 border border-slate-200 bg-white text-xs font-bold rounded-none">
                                                <option value="ASC">ASC</option>
                                                <option value="DESC">DESC</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Límite (Filas):</label>
                                        <input type="number" class="qb-limit-input w-full px-2 py-1 border border-slate-200 bg-white text-xs font-mono rounded-none" value="20" min="1" max="1000">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Bloques UNION / UNION ALL -->
                        <div class="p-3 bg-slate-50 border border-slate-200 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                    <i class="fa-solid fa-code-merge text-purple-600"></i> Uniones de Conjuntos (UNION / UNION ALL)
                                </span>
                                <button type="button" class="qb-btn-add-union px-2.5 py-1 bg-purple-600 hover:bg-purple-700 text-white text-[11px] font-bold rounded-none cursor-pointer">
                                    <i class="fa-solid fa-plus mr-1"></i> Agregar Bloque UNION
                                </button>
                            </div>
                            <div class="qb-unions-container space-y-2">
                                <div class="qb-unions-empty text-slate-400 text-xs italic py-2 text-center border border-dashed border-slate-200 bg-white">
                                    No hay bloques UNION activos. Puedes agregar conjuntos combinados si necesitas consolidar datos de múltiples tablas con la misma estructura.
                                </div>
                            </div>
                        </div>

                        <!-- Fragmentos Crudos Preservados (raw_blocks) si existen -->
                        <div class="qb-raw-blocks-wrapper hidden p-3 bg-amber-50 border border-amber-200 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-amber-800 text-xs flex items-center gap-1">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Fragmentos SQL Avanzados Preservados (CTEs / Subconsultas)
                                </span>
                            </div>
                            <div class="qb-raw-blocks-list space-y-1"></div>
                        </div>
                    </div>

                    <!-- Contenedor del Editor SQL Manual (Pestaña Script SQL) -->
                    <div class="qb-sql-panel hidden p-4 space-y-3 bg-slate-900 text-slate-100">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-300">
                            <span><i class="fa-solid fa-terminal text-emerald-400 mr-1.5"></i> Editor de Consulta SQL Compleja</span>
                            <div class="flex items-center gap-2">
                                <button type="button" class="qb-btn-convert-to-visual px-3 py-1 bg-datamind-primary hover:opacity-90 text-white text-xs font-bold rounded-none cursor-pointer">
                                    <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Convertir a Constructor Visual
                                </button>
                            </div>
                        </div>
                        <textarea class="qb-sql-textarea w-full px-3 py-2 border border-slate-700 bg-slate-950 text-emerald-300 font-mono text-xs rounded-none focus:border-emerald-500 focus:outline-hidden custom-scrollbar" rows="8" placeholder="SELECT u.id, u.name, r.name FROM users u JOIN role_user ru ON u.id = ru.user_id JOIN roles r ON ru.role_id = r.id;"></textarea>
                        <p class="text-[10px] text-slate-400 italic">
                            Puedes pegar cualquier script SQL con INNER/LEFT/RIGHT JOINs, UNIONs y CTEs. Al presionar "Convertir a Constructor", sqlglot analizará el AST y sincronizará la interfaz visual automáticamente.
                        </p>
                    </div>

                    <!-- Vista previa en vivo del SQL compilado y salida del Dry Run -->
                    <div class="border-t border-slate-200 bg-slate-950 text-slate-200 p-3 space-y-2">
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span class="text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa-solid fa-code text-datamind-primary"></i> SQL Compilado en Vivo (sqlglot)
                            </span>
                            <span class="qb-compile-status text-[10px] text-emerald-400">Sincronizado</span>
                        </div>
                        <pre class="qb-preview-sql font-mono text-xs text-emerald-300 bg-slate-900 p-2.5 overflow-x-auto custom-scrollbar border border-slate-800 rounded-none max-h-36">SELECT * FROM ...</pre>
                    </div>

                    <!-- Resultados del Dry Run (Modal o Sección desplegable) -->
                    <div class="qb-dry-run-results hidden border-t border-slate-200 bg-white p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                <i class="fa-solid fa-table text-emerald-600"></i> Resultado de la Prueba (<span class="qb-dry-count">0</span> filas obtenidas)
                            </span>
                            <button type="button" class="qb-btn-close-results text-slate-400 hover:text-slate-700 text-xs cursor-pointer">
                                <i class="fa-solid fa-xmark"></i> Cerrar Muestra
                            </button>
                        </div>
                        <div class="qb-dry-table-wrapper max-h-56 overflow-auto border border-slate-200 custom-scrollbar text-xs">
                            <table class="qb-dry-table w-full text-left border-collapse">
                                <thead class="bg-slate-100 text-slate-700 font-bold sticky top-0"></thead>
                                <tbody class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        bindEvents() {
            const root = this.container.querySelector('.qb-root');

            // Tabs
            const tabVisual = root.querySelector('.qb-tab-visual');
            const tabSql = root.querySelector('.qb-tab-sql');
            const visualPanel = root.querySelector('.qb-visual-panel');
            const sqlPanel = root.querySelector('.qb-sql-panel');

            tabVisual.addEventListener('click', () => {
                this.activeTab = 'visual';
                tabVisual.className = 'qb-tab-btn qb-tab-visual active px-3 py-1 text-xs font-bold bg-white text-datamind-primary border-t-2 border-datamind-primary rounded-none cursor-pointer';
                tabSql.className = 'qb-tab-btn qb-tab-sql px-3 py-1 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-200/60 rounded-none cursor-pointer';
                visualPanel.classList.remove('hidden');
                sqlPanel.classList.add('hidden');
            });

            tabSql.addEventListener('click', () => {
                this.activeTab = 'sql';
                tabSql.className = 'qb-tab-btn qb-tab-sql active px-3 py-1 text-xs font-bold bg-white text-datamind-primary border-t-2 border-datamind-primary rounded-none cursor-pointer';
                tabVisual.className = 'qb-tab-btn qb-tab-visual px-3 py-1 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-200/60 rounded-none cursor-pointer';
                sqlPanel.classList.remove('hidden');
                visualPanel.classList.add('hidden');

                // Llenar el textarea con el SQL actual
                const previewSql = root.querySelector('.qb-preview-sql').textContent;
                root.querySelector('.qb-sql-textarea').value = previewSql;
            });

            // Convertir SQL -> Visual
            root.querySelector('.qb-btn-convert-to-visual').addEventListener('click', () => {
                const sql = root.querySelector('.qb-sql-textarea').value.trim();
                if (!sql) {
                    this.notify('Por favor escribe o pega una consulta SQL.', 'error');
                    return;
                }
                this.parseSqlToVisual(sql);
            });

            // Inicializar Picker de Tabla Base
            const basePickerEl = root.querySelector('.qb-base-table-picker');
            if (window.TablePicker) {
                this.baseTablePicker = TablePicker.init(basePickerEl, {
                    getConnectionId: this.getConnectionId,
                    onSelect: (tableName) => {
                        this.state.base.table = tableName;
                        this.onBaseTableSelected(tableName);
                    }
                });
            }

            // Alias de Tabla Base
            root.querySelector('.qb-base-alias').addEventListener('input', (e) => {
                this.state.base.alias = e.target.value.trim() || 't1';
                this.scheduleCompile();
            });

            // Agregar JOIN
            root.querySelector('.qb-btn-add-join').addEventListener('click', () => {
                this.addJoinRow();
            });

            // Agregar Columna SELECT
            root.querySelector('.qb-btn-add-select').addEventListener('click', () => {
                this.addSelectRow();
            });

            // Agregar Filtro WHERE
            root.querySelector('.qb-btn-add-where').addEventListener('click', () => {
                this.addWhereRow();
            });

            // Agregar Bloque UNION
            root.querySelector('.qb-btn-add-union').addEventListener('click', () => {
                this.addUnionBlock();
            });

            // Ordenamiento y Límite
            root.querySelector('.qb-order-col').addEventListener('change', (e) => {
                const col = e.target.value;
                const dir = root.querySelector('.qb-order-dir').value;
                this.state.order_by = col ? [{ expr: col, direction: dir }] : [];
                this.scheduleCompile();
            });

            root.querySelector('.qb-order-dir').addEventListener('change', (e) => {
                const col = root.querySelector('.qb-order-col').value;
                const dir = e.target.value;
                this.state.order_by = col ? [{ expr: col, direction: dir }] : [];
                this.scheduleCompile();
            });

            root.querySelector('.qb-limit-input').addEventListener('input', (e) => {
                const val = parseInt(e.target.value, 10);
                this.state.limit = (!isNaN(val) && val > 0) ? val : 20;
                this.scheduleCompile();
            });

            // Botón Limpiar
            root.querySelector('.qb-btn-reset').addEventListener('click', () => {
                this.reset();
            });

            // Botón Dry Run
            root.querySelector('.qb-btn-dry-run').addEventListener('click', () => {
                this.runDryRun();
            });

            // Cerrar Muestra Dry Run
            root.querySelector('.qb-btn-close-results').addEventListener('click', () => {
                root.querySelector('.qb-dry-run-results').classList.add('hidden');
            });
        }

        async fetchTableColumns(tableName) {
            if (!tableName) return [];
            if (this.columnsCache.has(tableName)) {
                return this.columnsCache.get(tableName);
            }
            const connId = this.getConnectionId();
            if (!connId) return [];

            try {
                const res = await fetch(`/training/vectorized-tables/columns?connection_id=${encodeURIComponent(connId)}&table=${encodeURIComponent(tableName)}`);
                const json = await res.json();
                if (json.success && json.data) {
                    this.columnsCache.set(tableName, json.data);
                    return json.data;
                }
            } catch (e) {
                console.error('Error fetching columns:', e);
            }
            return [];
        }

        async onBaseTableSelected(tableName) {
            const cols = await this.fetchTableColumns(tableName);
            this.updateOrderColumnsDropdown();
            this.scheduleCompile();
        }

        updateOrderColumnsDropdown() {
            const root = this.container.querySelector('.qb-root');
            const select = root.querySelector('.qb-order-col');
            const currentVal = select.value;
            select.innerHTML = '<option value="">(Sin orden específico)</option>';

            const allTables = [this.state.base.table].concat(this.state.joins.map(j => j.table)).filter(Boolean);
            allTables.forEach(t => {
                const colsData = this.columnsCache.get(t);
                if (colsData && colsData.columns) {
                    colsData.columns.forEach(c => {
                        const opt = document.createElement('option');
                        const prefix = this.state.base.table === t ? this.state.base.alias : (this.state.joins.find(j => j.table === t)?.alias || t);
                        opt.value = `${prefix}.${c.name}`;
                        opt.textContent = `${prefix}.${c.name}`;
                        if (opt.value === currentVal) opt.selected = true;
                        select.appendChild(opt);
                    });
                }
            });
        }

        addJoinRow(joinData = null) {
            const root = this.container.querySelector('.qb-root');
            const container = root.querySelector('.qb-joins-container');
            const emptyNotice = root.querySelector('.qb-joins-empty');
            if (emptyNotice) emptyNotice.classList.add('hidden');

            const joinIndex = this.state.joins.length;
            const defaultAlias = `t${joinIndex + 2}`;
            const joinObj = joinData || {
                type: 'INNER',
                table: '',
                alias: defaultAlias,
                on: [{ left: '', op: '=', right: '' }]
            };
            if (!joinData) this.state.joins.push(joinObj);

            const row = document.createElement('div');
            row.className = 'qb-join-row p-2.5 bg-white border border-slate-200 rounded-none space-y-2';
            row.innerHTML = `
                <div class="flex items-center justify-between gap-2 pb-1 border-b border-slate-100">
                    <div class="flex items-center gap-1.5 flex-1">
                        <select class="qb-join-type px-2 py-1 border border-slate-200 bg-slate-50 font-bold text-indigo-700 text-xs rounded-none cursor-pointer">
                            <option value="INNER" ${joinObj.type === 'INNER' ? 'selected' : ''}>INNER JOIN</option>
                            <option value="LEFT" ${joinObj.type === 'LEFT' ? 'selected' : ''}>LEFT JOIN</option>
                            <option value="RIGHT" ${joinObj.type === 'RIGHT' ? 'selected' : ''}>RIGHT JOIN</option>
                            <option value="FULL" ${joinObj.type === 'FULL' ? 'selected' : ''}>FULL JOIN</option>
                            <option value="CROSS" ${joinObj.type === 'CROSS' ? 'selected' : ''}>CROSS JOIN</option>
                        </select>
                        <div class="qb-join-table-picker flex-1" data-table-picker data-placeholder="Seleccionar tabla a unir...">
                            <input type="hidden" class="qb-join-table-val" value="${esc(joinObj.table)}">
                        </div>
                        <input type="text" class="qb-join-alias w-16 px-2 py-1 border border-slate-200 font-mono text-xs rounded-none" value="${esc(joinObj.alias)}" placeholder="Alias">
                    </div>
                    <button type="button" class="qb-btn-remove-join text-slate-400 hover:text-red-600 text-xs p-1 cursor-pointer">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <div class="qb-join-on-container text-[11px] space-y-1">
                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-slate-600 uppercase text-[10px]">Condición ON:</span>
                        <input type="text" class="qb-on-left flex-1 px-2 py-1 border border-slate-200 font-mono rounded-none" placeholder="t1.id" value="${esc(joinObj.on[0]?.left || '')}">
                        <select class="qb-on-op px-1.5 py-1 border border-slate-200 bg-slate-50 font-mono rounded-none">
                            <option value="=">=</option>
                            <option value="!=">!=</option>
                        </select>
                        <input type="text" class="qb-on-right flex-1 px-2 py-1 border border-slate-200 font-mono rounded-none" placeholder="${esc(joinObj.alias)}.user_id" value="${esc(joinObj.on[0]?.right || '')}">
                    </div>
                </div>
            `;

            container.appendChild(row);

            // Inicializar Picker
            const pickerEl = row.querySelector('.qb-join-table-picker');
            if (window.TablePicker) {
                TablePicker.init(pickerEl, {
                    getConnectionId: this.getConnectionId,
                    onSelect: async (tableName) => {
                        joinObj.table = tableName;
                        await this.fetchTableColumns(tableName);
                        this.autoSuggestJoinOn(joinObj, row);
                        this.updateOrderColumnsDropdown();
                        this.scheduleCompile();
                    }
                });
            }

            row.querySelector('.qb-join-type').addEventListener('change', (e) => {
                joinObj.type = e.target.value;
                this.scheduleCompile();
            });

            row.querySelector('.qb-join-alias').addEventListener('input', (e) => {
                joinObj.alias = e.target.value.trim() || defaultAlias;
                this.scheduleCompile();
            });

            row.querySelector('.qb-on-left').addEventListener('input', (e) => {
                if (!joinObj.on[0]) joinObj.on.push({ left: '', op: '=', right: '' });
                joinObj.on[0].left = e.target.value.trim();
                this.scheduleCompile();
            });

            row.querySelector('.qb-on-right').addEventListener('input', (e) => {
                if (!joinObj.on[0]) joinObj.on.push({ left: '', op: '=', right: '' });
                joinObj.on[0].right = e.target.value.trim();
                this.scheduleCompile();
            });

            row.querySelector('.qb-btn-remove-join').addEventListener('click', () => {
                const idx = this.state.joins.indexOf(joinObj);
                if (idx !== -1) this.state.joins.splice(idx, 1);
                row.remove();
                if (this.state.joins.length === 0 && emptyNotice) emptyNotice.classList.remove('hidden');
                this.updateOrderColumnsDropdown();
                this.scheduleCompile();
            });

            this.scheduleCompile();
        }

        autoSuggestJoinOn(joinObj, rowEl) {
            const baseT = this.state.base.table;
            const targetT = joinObj.table;
            if (!baseT || !targetT) return;

            const baseCols = this.columnsCache.get(baseT);
            const targetCols = this.columnsCache.get(targetT);
            if (!baseCols || !targetCols) return;

            const baseAlias = this.state.base.alias;
            const targetAlias = joinObj.alias;

            // 1. Buscar FK en baseCols hacia targetT
            let matched = false;
            for (const c of (baseCols.columns || [])) {
                if (c.is_fk && c.references && c.references.table.toLowerCase() === targetT.toLowerCase()) {
                    const leftStr = `${baseAlias}.${c.name}`;
                    const rightStr = `${targetAlias}.${c.references.column || 'id'}`;
                    rowEl.querySelector('.qb-on-left').value = leftStr;
                    rowEl.querySelector('.qb-on-right').value = rightStr;
                    joinObj.on = [{ left: leftStr, op: '=', right: rightStr }];
                    matched = true;
                    break;
                }
            }

            // 2. Si no, buscar FK en targetCols hacia baseT
            if (!matched) {
                for (const c of (targetCols.columns || [])) {
                    if (c.is_fk && c.references && c.references.table.toLowerCase() === baseT.toLowerCase()) {
                        const leftStr = `${baseAlias}.${c.references.column || 'id'}`;
                        const rightStr = `${targetAlias}.${c.name}`;
                        rowEl.querySelector('.qb-on-left').value = leftStr;
                        rowEl.querySelector('.qb-on-right').value = rightStr;
                        joinObj.on = [{ left: leftStr, op: '=', right: rightStr }];
                        matched = true;
                        break;
                    }
                }
            }

            // 3. Fallback convención id = <singular>_id
            if (!matched) {
                const singularBase = baseT.replace(/s$/, '').replace(/es$/, '');
                const candFk = `${singularBase}_id`;
                const found = (targetCols.columns || []).some(c => c.name.toLowerCase() === candFk.toLowerCase());
                if (found) {
                    const leftStr = `${baseAlias}.id`;
                    const rightStr = `${targetAlias}.${candFk}`;
                    rowEl.querySelector('.qb-on-left').value = leftStr;
                    rowEl.querySelector('.qb-on-right').value = rightStr;
                    joinObj.on = [{ left: leftStr, op: '=', right: rightStr }];
                }
            }
        }

        addSelectRow(selectData = null) {
            const root = this.container.querySelector('.qb-root');
            const container = root.querySelector('.qb-selects-container');
            const emptyNotice = root.querySelector('.qb-selects-empty');
            if (emptyNotice) emptyNotice.classList.add('hidden');

            const item = selectData || { expr: '', alias: '', agg: null };
            if (!selectData) this.state.select.push(item);

            const row = document.createElement('div');
            row.className = 'qb-select-row flex items-center gap-1.5 p-1.5 bg-white border border-slate-200 rounded-none text-xs';
            row.innerHTML = `
                <select class="qb-select-agg px-1.5 py-1 border border-slate-200 bg-slate-50 font-bold text-emerald-700 text-xs rounded-none">
                    <option value="" ${!item.agg ? 'selected' : ''}>(Sin agregación)</option>
                    <option value="COUNT" ${item.agg === 'COUNT' ? 'selected' : ''}>COUNT</option>
                    <option value="SUM" ${item.agg === 'SUM' ? 'selected' : ''}>SUM</option>
                    <option value="AVG" ${item.agg === 'AVG' ? 'selected' : ''}>AVG</option>
                    <option value="MIN" ${item.agg === 'MIN' ? 'selected' : ''}>MIN</option>
                    <option value="MAX" ${item.agg === 'MAX' ? 'selected' : ''}>MAX</option>
                </select>
                <input type="text" class="qb-select-expr flex-1 px-2 py-1 border border-slate-200 font-mono text-xs rounded-none" placeholder="Columna o expr (ej. t1.name)" value="${esc(item.expr)}">
                <span class="text-slate-400 font-mono text-[10px]">AS</span>
                <input type="text" class="qb-select-alias w-32 px-2 py-1 border border-slate-200 font-mono text-xs rounded-none" placeholder="Alias (opcional)" value="${esc(item.alias || '')}">
                <button type="button" class="qb-btn-remove-select text-slate-400 hover:text-red-600 p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;

            container.appendChild(row);

            row.querySelector('.qb-select-agg').addEventListener('change', (e) => {
                item.agg = e.target.value || null;
                this.scheduleCompile();
            });

            row.querySelector('.qb-select-expr').addEventListener('input', (e) => {
                item.expr = e.target.value.trim();
                this.scheduleCompile();
            });

            row.querySelector('.qb-select-alias').addEventListener('input', (e) => {
                item.alias = e.target.value.trim() || null;
                this.scheduleCompile();
            });

            row.querySelector('.qb-btn-remove-select').addEventListener('click', () => {
                const idx = this.state.select.indexOf(item);
                if (idx !== -1) this.state.select.splice(idx, 1);
                row.remove();
                if (this.state.select.length === 0 && emptyNotice) emptyNotice.classList.remove('hidden');
                this.scheduleCompile();
            });

            this.scheduleCompile();
        }

        addWhereRow(whereData = null) {
            const root = this.container.querySelector('.qb-root');
            const container = root.querySelector('.qb-wheres-container');
            const emptyNotice = root.querySelector('.qb-wheres-empty');
            if (emptyNotice) emptyNotice.classList.add('hidden');

            const item = whereData || { left: '', op: '=', value: '', logic: 'AND' };
            if (!whereData) this.state.where.push(item);

            const row = document.createElement('div');
            row.className = 'qb-where-row flex items-center gap-1 p-1 bg-white border border-slate-200 rounded-none text-xs';
            row.innerHTML = `
                <select class="qb-where-logic px-1 py-0.5 border border-slate-200 bg-slate-50 font-bold text-[10px] rounded-none">
                    <option value="AND" ${item.logic === 'AND' ? 'selected' : ''}>AND</option>
                    <option value="OR" ${item.logic === 'OR' ? 'selected' : ''}>OR</option>
                </select>
                <input type="text" class="qb-where-left flex-1 px-1.5 py-0.5 border border-slate-200 font-mono text-[11px] rounded-none" placeholder="Campo" value="${esc(item.left)}">
                <select class="qb-where-op px-1 py-0.5 border border-slate-200 bg-slate-50 font-mono text-[11px] rounded-none">
                    <option value="=" ${item.op === '=' ? 'selected' : ''}>=</option>
                    <option value="!=" ${item.op === '!=' ? 'selected' : ''}>!=</option>
                    <option value=">" ${item.op === '>' ? 'selected' : ''}>&gt;</option>
                    <option value=">=" ${item.op === '>=' ? 'selected' : ''}>&gt;=</option>
                    <option value="<" ${item.op === '<' ? 'selected' : ''}>&lt;</option>
                    <option value="<=" ${item.op === '<=' ? 'selected' : ''}>&lt;=</option>
                    <option value="LIKE" ${item.op === 'LIKE' ? 'selected' : ''}>LIKE</option>
                    <option value="IS NULL" ${item.op === 'IS NULL' ? 'selected' : ''}>IS NULL</option>
                    <option value="IS NOT NULL" ${item.op === 'IS NOT NULL' ? 'selected' : ''}>IS NOT NULL</option>
                </select>
                <input type="text" class="qb-where-value flex-1 px-1.5 py-0.5 border border-slate-200 font-mono text-[11px] rounded-none" placeholder="Valor" value="${esc(item.value)}">
                <button type="button" class="qb-btn-remove-where text-slate-400 hover:text-red-600 p-0.5 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;

            container.appendChild(row);

            row.querySelector('.qb-where-logic').addEventListener('change', (e) => {
                item.logic = e.target.value;
                this.scheduleCompile();
            });

            row.querySelector('.qb-where-left').addEventListener('input', (e) => {
                item.left = e.target.value.trim();
                this.scheduleCompile();
            });

            row.querySelector('.qb-where-op').addEventListener('change', (e) => {
                item.op = e.target.value;
                this.scheduleCompile();
            });

            row.querySelector('.qb-where-value').addEventListener('input', (e) => {
                item.value = e.target.value.trim();
                this.scheduleCompile();
            });

            row.querySelector('.qb-btn-remove-where').addEventListener('click', () => {
                const idx = this.state.where.indexOf(item);
                if (idx !== -1) this.state.where.splice(idx, 1);
                row.remove();
                if (this.state.where.length === 0 && emptyNotice) emptyNotice.classList.remove('hidden');
                this.scheduleCompile();
            });

            this.scheduleCompile();
        }

        addUnionBlock(unionData = null) {
            const root = this.container.querySelector('.qb-root');
            const container = root.querySelector('.qb-unions-container');
            const emptyNotice = root.querySelector('.qb-unions-empty');
            if (emptyNotice) emptyNotice.classList.add('hidden');

            const item = unionData || {
                mode: 'UNION ALL',
                select_query: {
                    base: { table: '', alias: 'u1' },
                    joins: [],
                    select: [],
                    where: [],
                    group_by: [],
                    order_by: [],
                    limit: null
                }
            };
            if (!unionData) this.state.unions.push(item);

            const row = document.createElement('div');
            row.className = 'qb-union-row p-3 bg-white border border-purple-200 rounded-none space-y-2';
            row.innerHTML = `
                <div class="flex items-center justify-between pb-1.5 border-b border-purple-100">
                    <div class="flex items-center gap-2">
                        <select class="qb-union-mode px-2 py-1 bg-purple-50 border border-purple-200 text-purple-800 font-bold text-xs rounded-none">
                            <option value="UNION ALL" ${item.mode === 'UNION ALL' ? 'selected' : ''}>UNION ALL</option>
                            <option value="UNION" ${item.mode === 'UNION' ? 'selected' : ''}>UNION</option>
                        </select>
                        <span class="text-xs font-semibold text-slate-700">Bloque de Consulta Derivada</span>
                    </div>
                    <button type="button" class="qb-btn-remove-union text-slate-400 hover:text-red-600 text-xs p-1 cursor-pointer">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Tabla base del UNION:</label>
                        <div class="qb-union-table-picker" data-table-picker data-placeholder="Seleccionar tabla...">
                            <input type="hidden" class="qb-union-table-val" value="${esc(item.select_query.base.table)}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Columnas Proyectadas (coma separada):</label>
                        <input type="text" class="qb-union-cols w-full px-2 py-1 border border-slate-200 font-mono text-xs rounded-none" placeholder="id, email, nombre" value="${esc(item.select_query.select.map(s => s.expr).join(', '))}">
                    </div>
                </div>
            `;

            container.appendChild(row);

            // Picker de tabla para el bloque UNION
            const pickerEl = row.querySelector('.qb-union-table-picker');
            if (window.TablePicker) {
                TablePicker.init(pickerEl, {
                    getConnectionId: this.getConnectionId,
                    onSelect: (tableName) => {
                        item.select_query.base.table = tableName;
                        this.scheduleCompile();
                    }
                });
            }

            row.querySelector('.qb-union-mode').addEventListener('change', (e) => {
                item.mode = e.target.value;
                this.scheduleCompile();
            });

            row.querySelector('.qb-union-cols').addEventListener('input', (e) => {
                const cols = e.target.value.split(',').map(c => c.trim()).filter(Boolean);
                item.select_query.select = cols.map(c => ({ expr: c, alias: null, agg: null }));
                this.scheduleCompile();
            });

            row.querySelector('.qb-btn-remove-union').addEventListener('click', () => {
                const idx = this.state.unions.indexOf(item);
                if (idx !== -1) this.state.unions.splice(idx, 1);
                row.remove();
                if (this.state.unions.length === 0 && emptyNotice) emptyNotice.classList.remove('hidden');
                this.scheduleCompile();
            });

            this.scheduleCompile();
        }

        renderRawBlocks() {
            const root = this.container.querySelector('.qb-root');
            const wrapper = root.querySelector('.qb-raw-blocks-wrapper');
            const list = root.querySelector('.qb-raw-blocks-list');

            if (!this.state.raw_blocks || this.state.raw_blocks.length === 0) {
                wrapper.classList.add('hidden');
                list.innerHTML = '';
                return;
            }

            wrapper.classList.remove('hidden');
            list.innerHTML = this.state.raw_blocks.map(b => `
                <div class="p-2 bg-white border border-amber-300 font-mono text-[11px] text-amber-900 rounded-none">
                    <span class="font-bold text-[10px] uppercase text-amber-700 block">${esc(b.title || b.type)}</span>
                    <pre class="overflow-x-auto custom-scrollbar">${esc(b.content)}</pre>
                </div>
            `).join('');
        }

        scheduleCompile() {
            clearTimeout(this.compileDebounceTimer);
            this.compileDebounceTimer = setTimeout(() => {
                this.compileBlueprint();
            }, 300);
        }

        async compileBlueprint() {
            if (!this.state.base.table) {
                this.updatePreviewSql('-- Selecciona una tabla base para generar el SQL');
                return;
            }

            const root = this.container.querySelector('.qb-root');
            const statusEl = root.querySelector('.qb-compile-status');
            statusEl.textContent = 'Compilando...';
            statusEl.className = 'qb-compile-status text-[10px] text-amber-400';

            const payload = {
                blueprint: {
                    version: '1.0',
                    primary: {
                        base: this.state.base,
                        joins: this.state.joins,
                        select: this.state.select,
                        where: this.state.where,
                        group_by: this.state.group_by,
                        order_by: this.state.order_by,
                        limit: this.state.limit
                    },
                    unions: this.state.unions,
                    raw_blocks: this.state.raw_blocks
                },
                dialect: 'postgres'
            };

            try {
                const res = await fetch('/training/query-blueprint/compile', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.success && json.data) {
                    this.updatePreviewSql(json.data.sql);
                    statusEl.textContent = 'Sincronizado';
                    statusEl.className = 'qb-compile-status text-[10px] text-emerald-400';
                } else {
                    statusEl.textContent = 'Error de compilación';
                    statusEl.className = 'qb-compile-status text-[10px] text-red-400';
                }
            } catch (e) {
                console.error('Error compiling:', e);
                statusEl.textContent = 'Fallo de red';
                statusEl.className = 'qb-compile-status text-[10px] text-red-400';
            }
        }

        async parseSqlToVisual(sql) {
            const root = this.container.querySelector('.qb-root');
            try {
                const res = await fetch('/training/query-blueprint/parse', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ sql: sql, dialect: 'postgres' })
                });
                const json = await res.json();
                if (!json.success || !json.data) {
                    this.notify(json.message || 'No se pudo analizar la consulta SQL.', 'error');
                    return;
                }

                const bp = json.data.blueprint;
                this.loadBlueprintIntoVisual(bp);
                this.notify('Consulta analizada y sincronizada con el constructor visual.', 'success');

                // Cambiar a pestaña visual
                root.querySelector('.qb-tab-visual').click();

            } catch (e) {
                this.notify('Error al comunicarse con el servicio de análisis SQL.', 'error');
            }
        }

        loadBlueprintIntoVisual(bp) {
            const root = this.container.querySelector('.qb-root');
            this.reset(false);

            // 1. Tabla Base
            if (bp.primary && bp.primary.base) {
                this.state.base = bp.primary.base;
                if (this.baseTablePicker) {
                    this.baseTablePicker.setValue(this.state.base.table);
                }
                root.querySelector('.qb-base-alias').value = this.state.base.alias || 't1';
            }

            // 2. JOINs
            if (bp.primary && bp.primary.joins) {
                bp.primary.joins.forEach(j => this.addJoinRow(j));
            }

            // 3. SELECTs
            if (bp.primary && bp.primary.select) {
                bp.primary.select.forEach(s => this.addSelectRow(s));
            }

            // 4. WHEREs
            if (bp.primary && bp.primary.where) {
                bp.primary.where.forEach(w => this.addWhereRow(w));
            }

            // 5. ORDER BY y LIMIT
            if (bp.primary && bp.primary.order_by && bp.primary.order_by.length > 0) {
                this.state.order_by = bp.primary.order_by;
                root.querySelector('.qb-order-col').value = bp.primary.order_by[0].expr;
                root.querySelector('.qb-order-dir').value = bp.primary.order_by[0].direction || 'ASC';
            }
            if (bp.primary && bp.primary.limit) {
                this.state.limit = bp.primary.limit;
                root.querySelector('.qb-limit-input').value = bp.primary.limit;
            }

            // 6. UNIONs
            if (bp.unions) {
                bp.unions.forEach(u => this.addUnionBlock(u));
            }

            // 7. raw_blocks
            this.state.raw_blocks = bp.raw_blocks || [];
            this.renderRawBlocks();

            this.scheduleCompile();
        }

        async runDryRun() {
            const root = this.container.querySelector('.qb-root');
            const previewSql = root.querySelector('.qb-preview-sql').textContent;
            const connId = this.getConnectionId();

            if (!connId) {
                this.notify('Selecciona primero una base de datos vinculada.', 'error');
                return;
            }

            const btn = root.querySelector('.qb-btn-dry-run');
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Probando...';
            btn.disabled = true;

            try {
                const res = await fetch('/training/query-blueprint/dry-run', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        sql: previewSql,
                        connection_id: connId,
                        dialect: 'postgres'
                    })
                });
                const json = await res.json();
                if (!json.success || !json.data) {
                    this.notify(json.message || json.data?.error || 'Falló la ejecución de prueba.', 'error');
                    return;
                }

                const result = json.data;
                this.renderDryRunResults(result);
                this.notify(`Prueba exitosa: ${result.row_count} registros devueltos.`, 'success');

            } catch (e) {
                this.notify('Error de red al ejecutar la prueba.', 'error');
            } finally {
                btn.innerHTML = origHtml;
                btn.disabled = false;
            }
        }

        renderDryRunResults(result) {
            const root = this.container.querySelector('.qb-root');
            const resultsBox = root.querySelector('.qb-dry-run-results');
            const countEl = root.querySelector('.qb-dry-count');
            const thead = root.querySelector('.qb-dry-table thead');
            const tbody = root.querySelector('.qb-dry-table tbody');

            countEl.textContent = result.row_count;
            thead.innerHTML = '';
            tbody.innerHTML = '';

            if (result.columns && result.columns.length > 0) {
                const tr = document.createElement('tr');
                result.columns.forEach(col => {
                    const th = document.createElement('th');
                    th.className = 'px-3 py-1.5 border-b border-slate-200 text-slate-700 font-semibold';
                    th.textContent = col;
                    tr.appendChild(th);
                });
                thead.appendChild(tr);
            }

            if (result.rows && result.rows.length > 0) {
                result.rows.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50';
                    result.columns.forEach(col => {
                        const td = document.createElement('td');
                        td.className = 'px-3 py-1 border-b border-slate-100 font-mono text-[11px] text-slate-600 truncate max-w-xs';
                        td.textContent = row[col] !== null ? String(row[col]) : 'NULL';
                        tr.appendChild(td);
                    });
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="100%" class="px-3 py-4 text-center text-slate-400 italic">No se obtuvieron registros para esta consulta.</td></tr>';
            }

            resultsBox.classList.remove('hidden');
        }

        updatePreviewSql(sql) {
            const root = this.container.querySelector('.qb-root');
            const el = root.querySelector('.qb-preview-sql');
            el.textContent = sql;
            this.onSqlChange(sql, this.getBlueprint());
        }

        getBlueprint() {
            return {
                version: '1.0',
                primary: {
                    base: this.state.base,
                    joins: this.state.joins,
                    select: this.state.select,
                    where: this.state.where,
                    group_by: this.state.group_by,
                    order_by: this.state.order_by,
                    limit: this.state.limit
                },
                unions: this.state.unions,
                raw_blocks: this.state.raw_blocks
            };
        }

        getCompiledSql() {
            const root = this.container.querySelector('.qb-root');
            return root.querySelector('.qb-preview-sql').textContent;
        }

        reset(clearSqlPreview = true) {
            const root = this.container.querySelector('.qb-root');
            this.state = {
                base: { table: '', alias: 't1' },
                joins: [],
                select: [],
                where: [],
                group_by: [],
                order_by: [],
                limit: 20,
                unions: [],
                raw_blocks: []
            };

            if (this.baseTablePicker) this.baseTablePicker.reset();
            root.querySelector('.qb-base-alias').value = 't1';

            root.querySelector('.qb-joins-container').innerHTML = `
                <div class="qb-joins-empty text-slate-400 text-xs italic py-2 text-center border border-dashed border-slate-200 bg-white">
                    No hay uniones añadidas. Haz clic en "Agregar Unión" para vincular otra tabla (INNER, LEFT, RIGHT, FULL o CROSS).
                </div>
            `;
            root.querySelector('.qb-selects-container').innerHTML = `
                <div class="qb-selects-empty text-slate-400 text-xs italic py-2 text-center border border-dashed border-slate-200 bg-white">
                    Proyectando <code class="font-mono bg-slate-100 px-1">*</code> por defecto.
                </div>
            `;
            root.querySelector('.qb-wheres-container').innerHTML = `
                <div class="qb-wheres-empty text-slate-400 text-[11px] italic py-1.5 text-center border border-dashed border-slate-200 bg-white">
                    Sin filtros WHERE activos.
                </div>
            `;
            root.querySelector('.qb-unions-container').innerHTML = `
                <div class="qb-unions-empty text-slate-400 text-xs italic py-2 text-center border border-dashed border-slate-200 bg-white">
                    No hay bloques UNION activos.
                </div>
            `;

            root.querySelector('.qb-order-col').value = '';
            root.querySelector('.qb-order-dir').value = 'ASC';
            root.querySelector('.qb-limit-input').value = '20';
            root.querySelector('.qb-raw-blocks-wrapper').classList.add('hidden');
            root.querySelector('.qb-dry-run-results').classList.add('hidden');

            if (clearSqlPreview) {
                this.updatePreviewSql('-- Selecciona una tabla base para generar el SQL');
            }
        }
    }

    window.QueryBuilder = {
        init: function (options) {
            return new QueryBuilder(options);
        }
    };
})(window);
