/**
 * DataMind AI - QueryBuilder: Constructor visual interactivo y bidireccional de consultas compuestas complejas.
 *
 * Características:
 *  - Estado reactivo en memoria: tabla base, lista de JOINs (INNER, LEFT, RIGHT, FULL, CROSS),
 *    proyecciones SELECT (columnas, alias, agregaciones), filtros WHERE, GROUP BY, ORDER BY, LIMIT y UNIONs.
 *  - Selectores dinámicos en cascada de Alias y Columnas:
 *      * En condiciones ON: Selecciona alias izquierdo -> carga columnas de su tabla; selecciona alias derecho -> carga columnas de su tabla.
 *      * En condiciones WHERE: Selecciona alias -> carga columnas de esa tabla; operador dinámico y soporte de valores o columnas.
 *      * En proyecciones SELECT: Selecciona alias -> carga columnas de esa tabla con soporte de modo libre.
 *  - Integración nativa con TablePicker para paginar tablas vectorizadas de 10 en 10.
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

    /**
     * Genera un alias mnemotécnico e intuitivo basado en el nombre de la tabla:
     *  - Una palabra (ej: 'usuario' o 'roles'): 2 primeras letras ('us', 'ro').
     *  - Dos palabras (ej: 'usuario_empresa'): primera letra de la 1ra + 2 primeras de la 2da ('uem').
     *  - Tres o más palabras (ej: 'usuario_empresa_ola_mundo'): inicial de cada palabra ('ueom').
     */
    function generateSmartAlias(tableName, existingAliases = []) {
        if (!tableName) return '';
        const cleaned = String(tableName).toLowerCase().trim();
        const parts = cleaned.split(/[^a-z0-9]+/).filter(Boolean);
        if (parts.length === 0) return 't1';

        let baseAlias = '';
        if (parts.length === 1) {
            const w = parts[0];
            baseAlias = w.length >= 2 ? w.substring(0, 2) : w;
        } else if (parts.length === 2) {
            const p1 = parts[0];
            const p2 = parts[1];
            baseAlias = `${p1.charAt(0)}${p2.length >= 2 ? p2.substring(0, 2) : p2}`;
        } else {
            baseAlias = parts.map(p => p.charAt(0)).join('');
        }

        // Si ya existe ese alias en otra tabla de la misma query, agregar sufijo numérico (ej. us, us2, us3)
        let candidate = baseAlias;
        let counter = 2;
        const set = new Set(existingAliases.map(a => String(a).toLowerCase()));
        while (set.has(candidate.toLowerCase())) {
            candidate = `${baseAlias}${counter++}`;
        }
        return candidate;
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
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                                <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                    <span class="w-4 h-4 bg-datamind-primary text-white text-[10px] flex items-center justify-center font-bold">2</span>
                                    Tablas Unificadas (JOINs)
                                </span>
                                <button type="button" class="qb-btn-add-join px-3 py-1 bg-datamind-primary hover:opacity-90 text-white text-xs font-bold rounded-none cursor-pointer shadow-xs flex items-center gap-1">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>Agregar Unión</span>
                                </button>
                            </div>
                            <div class="qb-joins-container space-y-2">
                                <div class="qb-joins-empty text-slate-500 text-xs italic py-2 text-center border border-dashed border-slate-300 bg-white">
                                    No hay uniones añadidas. Haz clic en "Agregar Unión" para vincular otra tabla (INNER, LEFT, RIGHT, FULL o CROSS).
                                </div>
                            </div>
                        </div>

                        <!-- 3. Columnas Proyectadas (SELECT) -->
                        <div class="p-3 bg-slate-50 border border-slate-200 space-y-2.5">
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                                <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                    <span class="w-4 h-4 bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">3</span>
                                    Columnas Proyectadas (SELECT)
                                </span>
                                <button type="button" class="qb-btn-add-select px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-none cursor-pointer shadow-xs flex items-center gap-1">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>Agregar Columna</span>
                                </button>
                            </div>
                            <div class="qb-selects-container space-y-1.5">
                                <div class="qb-selects-empty text-slate-500 text-xs italic py-2 text-center border border-dashed border-slate-300 bg-white">
                                    Proyectando <code class="font-mono bg-slate-100 px-1 font-bold text-slate-700">*</code> por defecto. Agrega columnas específicas si deseas limitar los campos o aplicar agregaciones (COUNT, SUM, AVG, etc.).
                                </div>
                            </div>
                        </div>

                        <!-- 4. Filtros (WHERE), Orden (ORDER BY) y Límite -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                            <!-- Filtros WHERE -->
                            <div class="p-3 bg-slate-50 border border-slate-200 space-y-2">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200">
                                    <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                        <i class="fa-solid fa-filter text-amber-600"></i> Filtros (WHERE)
                                    </span>
                                    <button type="button" class="qb-btn-add-where px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold rounded-none cursor-pointer shadow-xs flex items-center gap-1">
                                        <i class="fa-solid fa-plus text-[9px]"></i>
                                        <span>Filtro</span>
                                    </button>
                                </div>
                                <div class="qb-wheres-container space-y-1.5">
                                    <div class="qb-wheres-empty text-slate-500 text-[11px] italic py-1.5 text-center border border-dashed border-slate-300 bg-white">
                                        Sin filtros WHERE activos.
                                    </div>
                                </div>
                            </div>

                            <!-- Ordenamiento y Límite -->
                            <div class="p-3 bg-slate-50 border border-slate-200 space-y-2">
                                <div class="pb-1 border-b border-slate-200">
                                    <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                        <i class="fa-solid fa-arrow-down-wide-short text-slate-700"></i> Orden y Límite
                                    </span>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-2">
                                        <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Ordenar por:</label>
                                        <div class="flex gap-1">
                                            <select class="qb-order-col w-full px-2 py-1.5 border border-slate-300 bg-white text-xs font-mono text-slate-800 rounded-none focus:outline-hidden">
                                                <option value="">(Sin orden específico)</option>
                                            </select>
                                            <select class="qb-order-dir px-2 py-1.5 border border-slate-300 bg-white text-xs font-bold text-slate-800 rounded-none focus:outline-hidden">
                                                <option value="ASC">ASC</option>
                                                <option value="DESC">DESC</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Límite (Filas):</label>
                                        <input type="number" class="qb-limit-input w-full px-2 py-1.5 border border-slate-300 bg-white text-xs font-mono text-slate-800 rounded-none focus:outline-hidden" value="20" min="1" max="1000">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Bloques UNION / UNION ALL -->
                        <div class="p-3 bg-slate-50 border border-slate-200 space-y-2.5">
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                                <span class="font-bold text-slate-800 uppercase text-[11px] flex items-center gap-1.5">
                                    <i class="fa-solid fa-code-merge text-purple-600"></i> Uniones de Conjuntos (UNION / UNION ALL)
                                </span>
                                <button type="button" class="qb-btn-add-union px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-none cursor-pointer shadow-xs flex items-center gap-1">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>Agregar Bloque UNION</span>
                                </button>
                            </div>
                            <div class="qb-unions-container space-y-2">
                                <div class="qb-unions-empty text-slate-500 text-xs italic py-2 text-center border border-dashed border-slate-300 bg-white">
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
                this.refreshAllAliasSelectors();
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

        /**
         * Retorna la lista de todas las fuentes disponibles en la consulta primaria:
         * [{ alias: 't1', table: 'users', label: 't1 (users)' }, { alias: 'ru', table: 'role_user', ... }]
         */
        getAvailableSources() {
            const sources = [];
            if (this.state.base.table) {
                sources.push({
                    alias: this.state.base.alias || 't1',
                    table: this.state.base.table,
                    label: `${this.state.base.alias || 't1'} (${this.state.base.table})`
                });
            }
            this.state.joins.forEach((j, idx) => {
                if (j.table) {
                    const a = j.alias || `t${idx + 2}`;
                    sources.push({
                        alias: a,
                        table: j.table,
                        label: `${a} (${j.table})`
                    });
                }
            });
            return sources;
        }

        getTableForAlias(alias) {
            if (!alias) return '';
            if (alias === this.state.base.alias) return this.state.base.table;
            const found = this.state.joins.find(j => j.alias === alias);
            return found ? found.table : '';
        }

        async fetchTableColumns(tableName) {
            if (!tableName) return null;
            if (this.columnsCache.has(tableName)) {
                return this.columnsCache.get(tableName);
            }
            const connId = this.getConnectionId();
            if (!connId) return null;

            try {
                const res = await fetch(`/training/vectorized-tables/columns?connection_id=${encodeURIComponent(connId)}&table=${encodeURIComponent(tableName)}`);
                const json = await res.json();
                if (json.success) {
                    const data = json.data || json;
                    this.columnsCache.set(tableName, data);
                    return data;
                }
            } catch (e) {
                console.error('Error fetching columns:', e);
            }
            return null;
        }

        async onBaseTableSelected(tableName) {
            // Generar alias mnemotécnico si el usuario no ingresó uno propio personalizado
            const currentAlias = (this.state.base.alias || '').trim();
            if (!currentAlias || currentAlias === 't1' || /^t\d+$/.test(currentAlias)) {
                const smart = generateSmartAlias(tableName, []);
                this.state.base.alias = smart;
                const aliasInput = this.container.querySelector('.qb-base-alias');
                if (aliasInput) aliasInput.value = smart;
            }

            await this.fetchTableColumns(tableName);
            this.refreshAllAliasSelectors();
            this.updateOrderColumnsDropdown();
            this.scheduleCompile();
        }

        /**
         * Actualiza todos los selectores de alias en las condiciones ON, WHERE y SELECT.
         */
        refreshAllAliasSelectors() {
            const root = this.container.querySelector('.qb-root');
            const sources = this.getAvailableSources();

            // 1. En cada fila de JOIN (ON izquierdo y derecho)
            root.querySelectorAll('.qb-join-row').forEach(row => {
                const leftAliasSel = row.querySelector('.qb-on-left-alias');
                const rightAliasSel = row.querySelector('.qb-on-right-alias');
                if (leftAliasSel) this._fillAliasOptions(leftAliasSel, sources);
                if (rightAliasSel) this._fillAliasOptions(rightAliasSel, sources);
            });

            // 2. En cada fila de WHERE
            root.querySelectorAll('.qb-where-row').forEach(row => {
                const aliasSel = row.querySelector('.qb-where-alias');
                if (aliasSel) this._fillAliasOptions(aliasSel, sources);
            });

            // 3. En cada fila de SELECT
            root.querySelectorAll('.qb-select-row').forEach(row => {
                const aliasSel = row.querySelector('.qb-select-alias-src');
                if (aliasSel) this._fillAliasOptions(aliasSel, sources);
            });

            this.updateOrderColumnsDropdown();
        }

        _fillAliasOptions(selectEl, sources) {
            const currentVal = selectEl.value;
            selectEl.innerHTML = '<option value="">-- Tabla/Alias --</option>';
            sources.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.alias;
                opt.textContent = s.label;
                if (opt.value === currentVal) opt.selected = true;
                selectEl.appendChild(opt);
            });
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

            // Desglosar condición ON existente: "t1.id" -> alias "t1", col "id"
            let leftAlias = this.state.base.alias || '';
            let leftCol = '';
            let rightAlias = joinObj.alias || '';
            let rightCol = '';
            let onOp = '=';

            if (joinObj.on && joinObj.on[0]) {
                onOp = joinObj.on[0].op || '=';
                const lParts = (joinObj.on[0].left || '').split('.');
                if (lParts.length === 2) { leftAlias = lParts[0]; leftCol = lParts[1]; }
                else { leftCol = joinObj.on[0].left || ''; }

                const rParts = (joinObj.on[0].right || '').split('.');
                if (rParts.length === 2) { rightAlias = rParts[0]; rightCol = rParts[1]; }
                else { rightCol = joinObj.on[0].right || ''; }
            }

            const row = document.createElement('div');
            row.className = 'qb-join-row p-3 bg-white border border-slate-300 rounded-none space-y-2.5 shadow-2xs';
            row.innerHTML = `
                <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-200">
                    <div class="flex items-center gap-2 flex-1">
                        <select class="qb-join-type px-2.5 py-1.5 border border-slate-300 bg-slate-100 font-bold text-slate-900 text-xs rounded-none cursor-pointer focus:outline-hidden">
                            <option value="INNER" ${joinObj.type === 'INNER' ? 'selected' : ''}>INNER JOIN</option>
                            <option value="LEFT" ${joinObj.type === 'LEFT' ? 'selected' : ''}>LEFT JOIN</option>
                            <option value="RIGHT" ${joinObj.type === 'RIGHT' ? 'selected' : ''}>RIGHT JOIN</option>
                            <option value="FULL" ${joinObj.type === 'FULL' ? 'selected' : ''}>FULL JOIN</option>
                            <option value="CROSS" ${joinObj.type === 'CROSS' ? 'selected' : ''}>CROSS JOIN</option>
                        </select>
                        <div class="qb-join-table-picker flex-1" data-table-picker data-placeholder="Seleccionar tabla a unir...">
                            <input type="hidden" class="qb-join-table-val" value="${esc(joinObj.table)}">
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] font-semibold text-slate-500 uppercase">Alias:</span>
                            <input type="text" class="qb-join-alias w-16 px-2 py-1.5 border border-slate-300 bg-white font-mono text-xs text-slate-800 rounded-none focus:outline-hidden" value="${esc(joinObj.alias)}" placeholder="Alias">
                        </div>
                    </div>
                    <button type="button" class="qb-btn-remove-join text-slate-400 hover:text-red-600 hover:bg-red-50 text-xs px-2 py-1 rounded-none cursor-pointer transition">
                        <i class="fa-solid fa-trash mr-1"></i> Quitar
                    </button>
                </div>

                <!-- Selector guiado en Cascada de la condición ON -->
                <div class="qb-join-on-container text-[11px] p-2.5 bg-slate-100/70 border border-slate-200 space-y-1.5">
                    <span class="font-bold text-slate-700 uppercase text-[10px] flex items-center gap-1.5">
                        <i class="fa-solid fa-link text-datamind-primary"></i> Condición de Unión (ON):
                    </span>
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Lado Izquierdo: Alias -> Columna -->
                        <div class="flex items-center gap-1 flex-1 min-w-[220px]">
                            <select class="qb-on-left-alias w-1/2 px-2 py-1.5 border border-slate-300 bg-white font-mono text-xs text-slate-800 rounded-none cursor-pointer focus:outline-hidden">
                                <option value="">-- Alias --</option>
                            </select>
                            <select class="qb-on-left-col w-1/2 px-2 py-1.5 border border-slate-300 bg-white font-mono text-xs text-slate-800 rounded-none cursor-pointer focus:outline-hidden disabled:bg-slate-100 disabled:text-slate-400" disabled>
                                <option value="">-- Columna --</option>
                            </select>
                        </div>

                        <!-- Operador -->
                        <select class="qb-on-op px-2.5 py-1.5 border border-slate-300 bg-white font-mono font-bold text-xs text-slate-800 rounded-none focus:outline-hidden">
                            <option value="=" ${onOp === '=' ? 'selected' : ''}>=</option>
                            <option value="!=" ${onOp === '!=' ? 'selected' : ''}>!=</option>
                        </select>

                        <!-- Lado Derecho: Alias -> Columna -->
                        <div class="flex items-center gap-1 flex-1 min-w-[220px]">
                            <select class="qb-on-right-alias w-1/2 px-2 py-1.5 border border-slate-300 bg-white font-mono text-xs text-slate-800 rounded-none cursor-pointer focus:outline-hidden">
                                <option value="">-- Alias --</option>
                            </select>
                            <select class="qb-on-right-col w-1/2 px-2 py-1.5 border border-slate-300 bg-white font-mono text-xs text-slate-800 rounded-none cursor-pointer focus:outline-hidden disabled:bg-slate-100 disabled:text-slate-400" disabled>
                                <option value="">-- Columna --</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;

            container.appendChild(row);

            const leftAliasSel = row.querySelector('.qb-on-left-alias');
            const leftColSel = row.querySelector('.qb-on-left-col');
            const rightAliasSel = row.querySelector('.qb-on-right-alias');
            const rightColSel = row.querySelector('.qb-on-right-col');
            const opSel = row.querySelector('.qb-on-op');

            // Manejadores en cascada de Columnas
            const updateColsForAlias = async (aliasSel, colSel, targetColVal) => {
                const a = aliasSel.value;
                if (!a) {
                    colSel.innerHTML = '<option value="">-- Elige tabla --</option>';
                    colSel.disabled = true;
                    return;
                }
                const t = this.getTableForAlias(a);
                if (!t) {
                    colSel.innerHTML = '<option value="">-- Sin tabla --</option>';
                    colSel.disabled = true;
                    return;
                }
                colSel.innerHTML = '<option value="">Cargando columnas...</option>';
                colSel.disabled = true;

                const colsData = await this.fetchTableColumns(t);
                colSel.innerHTML = '<option value="">-- Seleccionar Columna --</option>';
                if (colsData && colsData.columns && colsData.columns.length > 0) {
                    colsData.columns.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.name;
                        opt.textContent = `${c.name} (${c.type || 'varchar'})${c.is_fk ? ' [FK]' : ''}`;
                        if (c.name === targetColVal) opt.selected = true;
                        colSel.appendChild(opt);
                    });
                    colSel.disabled = false;
                } else {
                    colSel.innerHTML = '<option value="">(Sin columnas)</option>';
                    colSel.disabled = true;
                }
            };

            // Inicializar Picker de Tabla
            const pickerEl = row.querySelector('.qb-join-table-picker');
            const aliasInput = row.querySelector('.qb-join-alias');
            if (window.TablePicker) {
                TablePicker.init(pickerEl, {
                    getConnectionId: this.getConnectionId,
                    onSelect: async (tableName) => {
                        joinObj.table = tableName;
                        // Autocalcular alias inteligente según las reglas (1 palabra -> 2 letras, 2 palabras -> 1ra + 2 letras, 3+ -> siglas)
                        const existingAliases = this.getAvailableSources().map(s => s.alias).filter(a => a && a !== joinObj.alias);
                        const smartAlias = generateSmartAlias(tableName, existingAliases);
                        joinObj.alias = smartAlias;
                        if (aliasInput) {
                            aliasInput.value = smartAlias;
                        }

                        await this.fetchTableColumns(tableName);
                        this.refreshAllAliasSelectors();

                        // Si autoSuggestJoinOn logra enlazar, actualiza; si no, poblar columnas de la tabla unida
                        const suggested = await this.autoSuggestJoinOn(joinObj, row);
                        if (!suggested) {
                            // Por defecto lado derecho = tabla unida
                            rightAliasSel.value = smartAlias;
                            await updateColsForAlias(rightAliasSel, rightColSel, '');
                            // Si lado izquierdo no tiene alias, usar base
                            if (!leftAliasSel.value && this.state.base.alias) {
                                leftAliasSel.value = this.state.base.alias;
                                await updateColsForAlias(leftAliasSel, leftColSel, '');
                            }
                        }

                        this.scheduleCompile();
                    }
                });
            }

            // Llenar selects de alias
            const sources = this.getAvailableSources();
            this._fillAliasOptions(leftAliasSel, sources);
            this._fillAliasOptions(rightAliasSel, sources);

            if (leftAlias) leftAliasSel.value = leftAlias;
            if (rightAlias) rightAliasSel.value = rightAlias;

            leftAliasSel.addEventListener('change', () => {
                updateColsForAlias(leftAliasSel, leftColSel, '');
                syncOnObj();
            });

            rightAliasSel.addEventListener('change', () => {
                updateColsForAlias(rightAliasSel, rightColSel, '');
                syncOnObj();
            });

            const syncOnObj = () => {
                const lA = leftAliasSel.value;
                const lC = leftColSel.value;
                const rA = rightAliasSel.value;
                const rC = rightColSel.value;
                const op = opSel.value;

                const leftStr = (lA && lC) ? `${lA}.${lC}` : (lC || '');
                const rightStr = (rA && rC) ? `${rA}.${rC}` : (rC || '');

                joinObj.on = [{ left: leftStr, op: op, right: rightStr }];
                this.scheduleCompile();
            };

            leftColSel.addEventListener('change', syncOnObj);
            rightColSel.addEventListener('change', syncOnObj);
            opSel.addEventListener('change', syncOnObj);

            // Si vienen valores iniciales, poblar
            if (leftAlias) updateColsForAlias(leftAliasSel, leftColSel, leftCol);
            if (rightAlias) updateColsForAlias(rightAliasSel, rightColSel, rightCol);

            row.querySelector('.qb-join-type').addEventListener('change', (e) => {
                joinObj.type = e.target.value;
                this.scheduleCompile();
            });

            row.querySelector('.qb-join-alias').addEventListener('input', (e) => {
                joinObj.alias = e.target.value.trim() || defaultAlias;
                this.refreshAllAliasSelectors();
                this.scheduleCompile();
            });

            row.querySelector('.qb-btn-remove-join').addEventListener('click', () => {
                const idx = this.state.joins.indexOf(joinObj);
                if (idx !== -1) this.state.joins.splice(idx, 1);
                row.remove();
                if (this.state.joins.length === 0 && emptyNotice) emptyNotice.classList.remove('hidden');
                this.refreshAllAliasSelectors();
                this.scheduleCompile();
            });

            this.refreshAllAliasSelectors();
            this.scheduleCompile();
        }

        async autoSuggestJoinOn(joinObj, rowEl) {
            const baseT = this.state.base.table;
            const targetT = joinObj.table;
            if (!baseT || !targetT) return;

            const baseCols = await this.fetchTableColumns(baseT);
            const targetCols = await this.fetchTableColumns(targetT);
            if (!baseCols || !targetCols) return;

            const baseAlias = this.state.base.alias;
            const targetAlias = joinObj.alias;

            let matched = false;
            let lAlias = baseAlias, lCol = '', rAlias = targetAlias, rCol = '';

            // 1. Buscar FK en baseCols hacia targetT
            for (const c of (baseCols.columns || [])) {
                if (c.is_fk && c.references && c.references.table.toLowerCase() === targetT.toLowerCase()) {
                    lCol = c.name;
                    rCol = c.references.column || 'id';
                    matched = true;
                    break;
                }
            }

            // 2. Si no, buscar FK en targetCols hacia baseT
            if (!matched) {
                for (const c of (targetCols.columns || [])) {
                    if (c.is_fk && c.references && c.references.table.toLowerCase() === baseT.toLowerCase()) {
                        lCol = c.references.column || 'id';
                        rCol = c.name;
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
                    lCol = 'id';
                    rCol = candFk;
                    matched = true;
                }
            }

            if (matched) {
                const leftAliasSel = rowEl.querySelector('.qb-on-left-alias');
                const leftColSel = rowEl.querySelector('.qb-on-left-col');
                const rightAliasSel = rowEl.querySelector('.qb-on-right-alias');
                const rightColSel = rowEl.querySelector('.qb-on-right-col');

                leftAliasSel.value = lAlias;
                rightAliasSel.value = rAlias;

                // Cargar selects
                const fillAndSet = (colSel, colsData, val) => {
                    colSel.innerHTML = '<option value="">-- Seleccionar Columna --</option>';
                    (colsData.columns || []).forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.name;
                        opt.textContent = `${c.name} (${c.type || 'varchar'})${c.is_fk ? ' [FK]' : ''}`;
                        if (c.name === val) opt.selected = true;
                        colSel.appendChild(opt);
                    });
                    colSel.disabled = false;
                };

                fillAndSet(leftColSel, baseCols, lCol);
                fillAndSet(rightColSel, targetCols, rCol);

                joinObj.on = [{ left: `${lAlias}.${lCol}`, op: '=', right: `${rAlias}.${rCol}` }];
                return true;
            }
            return false;
        }

        addSelectRow(selectData = null) {
            const root = this.container.querySelector('.qb-root');
            const container = root.querySelector('.qb-selects-container');
            const emptyNotice = root.querySelector('.qb-selects-empty');
            if (emptyNotice) emptyNotice.classList.add('hidden');

            const item = selectData || { expr: '', alias: '', agg: null };
            if (!selectData) this.state.select.push(item);

            // Desglosar alias y columna si viene en formato "alias.col"
            let initialAlias = '';
            let initialCol = item.expr;
            if (item.expr && item.expr.includes('.')) {
                const parts = item.expr.split('.');
                initialAlias = parts[0];
                initialCol = parts[1];
            } else if (this.state.base.alias) {
                initialAlias = this.state.base.alias;
            }

            const row = document.createElement('div');
            row.className = 'qb-select-row flex flex-wrap items-center gap-1.5 p-1.5 bg-white border border-slate-200 rounded-none text-xs';
            row.innerHTML = `
                <select class="qb-select-agg px-1.5 py-1 border border-slate-200 bg-slate-50 font-bold text-emerald-700 text-xs rounded-none cursor-pointer">
                    <option value="" ${!item.agg ? 'selected' : ''}>(Sin agregación)</option>
                    <option value="COUNT" ${item.agg === 'COUNT' ? 'selected' : ''}>COUNT</option>
                    <option value="SUM" ${item.agg === 'SUM' ? 'selected' : ''}>SUM</option>
                    <option value="AVG" ${item.agg === 'AVG' ? 'selected' : ''}>AVG</option>
                    <option value="MIN" ${item.agg === 'MIN' ? 'selected' : ''}>MIN</option>
                    <option value="MAX" ${item.agg === 'MAX' ? 'selected' : ''}>MAX</option>
                </select>

                <!-- Selector de Tabla/Alias -->
                <select class="qb-select-alias-src px-1.5 py-1 border border-slate-200 bg-white font-mono text-xs rounded-none cursor-pointer min-w-[120px]">
                    <option value="">-- Tabla/Alias --</option>
                </select>

                <!-- Selector de Columna -->
                <select class="qb-select-col-dropdown px-1.5 py-1 border border-slate-200 bg-white font-mono text-xs rounded-none cursor-pointer flex-1 min-w-[140px]" disabled>
                    <option value="">-- Columna --</option>
                </select>

                <span class="text-slate-400 font-mono text-[10px]">AS</span>
                <input type="text" class="qb-select-alias w-28 px-2 py-1 border border-slate-200 font-mono text-xs rounded-none" placeholder="Alias (opcional)" value="${esc(item.alias || '')}">
                <button type="button" class="qb-btn-remove-select text-slate-400 hover:text-red-600 p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;

            container.appendChild(row);

            const aliasSel = row.querySelector('.qb-select-alias-src');
            const colSel = row.querySelector('.qb-select-col-dropdown');
            const aggSel = row.querySelector('.qb-select-agg');
            const asInput = row.querySelector('.qb-select-alias');

            this._fillAliasOptions(aliasSel, this.getAvailableSources());
            if (initialAlias) aliasSel.value = initialAlias;

            const updateCols = async (targetVal) => {
                const a = aliasSel.value;
                if (!a) {
                    colSel.innerHTML = '<option value="">-- Elige tabla --</option>';
                    colSel.disabled = true;
                    return;
                }
                const t = this.getTableForAlias(a);
                const colsData = await this.fetchTableColumns(t);
                colSel.innerHTML = '<option value="*">* (Todas)</option>';
                if (colsData && colsData.columns) {
                    colsData.columns.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.name;
                        opt.textContent = `${c.name} (${c.type || 'varchar'})`;
                        if (c.name === targetVal) opt.selected = true;
                        colSel.appendChild(opt);
                    });
                    colSel.disabled = false;
                }
            };

            aliasSel.addEventListener('change', () => {
                updateCols('');
                syncItem();
            });

            const syncItem = () => {
                const a = aliasSel.value;
                const c = colSel.value;
                item.expr = (a && c && c !== '*') ? `${a}.${c}` : (c === '*' && a ? `${a}.*` : (c || ''));
                this.scheduleCompile();
            };

            colSel.addEventListener('change', syncItem);
            aggSel.addEventListener('change', (e) => {
                item.agg = e.target.value || null;
                this.scheduleCompile();
            });
            asInput.addEventListener('input', (e) => {
                item.alias = e.target.value.trim() || null;
                this.scheduleCompile();
            });

            if (initialAlias) updateCols(initialCol);

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

            // Desglosar alias y columna izquierda: "u.status" -> alias "u", col "status"
            let initialAlias = '';
            let initialCol = item.left;
            if (item.left && item.left.includes('.')) {
                const parts = item.left.split('.');
                initialAlias = parts[0];
                initialCol = parts[1];
            } else if (this.state.base.alias) {
                initialAlias = this.state.base.alias;
            }

            const row = document.createElement('div');
            row.className = 'qb-where-row flex flex-wrap items-center gap-1 p-1.5 bg-white border border-slate-200 rounded-none text-xs';
            row.innerHTML = `
                <select class="qb-where-logic px-1 py-1 border border-slate-200 bg-slate-50 font-bold text-[10px] rounded-none">
                    <option value="AND" ${item.logic === 'AND' ? 'selected' : ''}>AND</option>
                    <option value="OR" ${item.logic === 'OR' ? 'selected' : ''}>OR</option>
                </select>

                <!-- Selector de Tabla/Alias para el campo -->
                <select class="qb-where-alias px-1.5 py-1 border border-slate-200 bg-white font-mono text-xs rounded-none cursor-pointer min-w-[110px]">
                    <option value="">-- Tabla/Alias --</option>
                </select>

                <!-- Selector de Columna de esa tabla -->
                <select class="qb-where-col px-1.5 py-1 border border-slate-200 bg-white font-mono text-xs rounded-none cursor-pointer min-w-[120px]" disabled>
                    <option value="">-- Columna --</option>
                </select>

                <!-- Operador -->
                <select class="qb-where-op px-1 py-1 border border-slate-200 bg-slate-50 font-mono text-[11px] rounded-none">
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

                <!-- Valor o expresión evaluada -->
                <input type="text" class="qb-where-value flex-1 px-1.5 py-1 border border-slate-200 font-mono text-xs rounded-none min-w-[100px]" placeholder="Valor o 1" value="${esc(item.value)}">

                <button type="button" class="qb-btn-remove-where text-slate-400 hover:text-red-600 p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;

            container.appendChild(row);

            const logicSel = row.querySelector('.qb-where-logic');
            const aliasSel = row.querySelector('.qb-where-alias');
            const colSel = row.querySelector('.qb-where-col');
            const opSel = row.querySelector('.qb-where-op');
            const valInput = row.querySelector('.qb-where-value');

            this._fillAliasOptions(aliasSel, this.getAvailableSources());
            if (initialAlias) aliasSel.value = initialAlias;

            const updateWhereCols = async (targetVal) => {
                const a = aliasSel.value;
                if (!a) {
                    colSel.innerHTML = '<option value="">-- Elige tabla --</option>';
                    colSel.disabled = true;
                    return;
                }
                const t = this.getTableForAlias(a);
                const colsData = await this.fetchTableColumns(t);
                colSel.innerHTML = '<option value="">-- Seleccionar Columna --</option>';
                if (colsData && colsData.columns) {
                    colsData.columns.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.name;
                        opt.textContent = `${c.name} (${c.type || 'varchar'})${c.is_fk ? ' [FK]' : ''}`;
                        if (c.name === targetVal) opt.selected = true;
                        colSel.appendChild(opt);
                    });
                    colSel.disabled = false;
                }
            };

            aliasSel.addEventListener('change', () => {
                updateWhereCols('');
                syncWhere();
            });

            const syncWhere = () => {
                const a = aliasSel.value;
                const c = colSel.value;
                item.left = (a && c) ? `${a}.${c}` : (c || '');
                item.op = opSel.value;
                item.value = valInput.value.trim();
                item.logic = logicSel.value;

                // Si es IS NULL o IS NOT NULL, deshabilitar input de valor
                if (item.op === 'IS NULL' || item.op === 'IS NOT NULL') {
                    valInput.disabled = true;
                    valInput.classList.add('bg-slate-100', 'text-slate-400');
                } else {
                    valInput.disabled = false;
                    valInput.classList.remove('bg-slate-100', 'text-slate-400');
                }

                this.scheduleCompile();
            };

            colSel.addEventListener('change', syncWhere);
            opSel.addEventListener('change', syncWhere);
            valInput.addEventListener('input', syncWhere);
            logicSel.addEventListener('change', syncWhere);

            if (initialAlias) updateWhereCols(initialCol);

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
                blueprint: this.getBlueprint(),
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
                await this.loadBlueprintIntoVisual(bp);
                this.notify('Consulta analizada y sincronizada con el constructor visual.', 'success');

                // Cambiar a pestaña visual
                root.querySelector('.qb-tab-visual').click();

            } catch (e) {
                this.notify('Error al comunicarse con el servicio de análisis SQL.', 'error');
            }
        }

        async loadBlueprintIntoVisual(bp) {
            const root = this.container.querySelector('.qb-root');
            this.reset(false);

            // 1. Tabla Base
            if (bp.primary && bp.primary.base) {
                this.state.base = bp.primary.base;
                if (this.baseTablePicker) {
                    this.baseTablePicker.setValue(this.state.base.table);
                }
                root.querySelector('.qb-base-alias').value = this.state.base.alias || 't1';
                if (this.state.base.table) {
                    await this.fetchTableColumns(this.state.base.table);
                }
            }

            // Precargar columnas de todas las tablas de JOIN
            if (bp.primary && bp.primary.joins) {
                for (const j of bp.primary.joins) {
                    if (j.table) await this.fetchTableColumns(j.table);
                }
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

            this.refreshAllAliasSelectors();
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
            // Limpiar y garantizar tipos string válidos antes de serializar
            const cleanJoins = this.state.joins.map(j => {
                const cleanOn = (j.on || []).map(o => ({
                    left: String(o.left ?? '').trim(),
                    op: String(o.op ?? '=').trim(),
                    right: String(o.right ?? '').trim()
                }));
                return {
                    type: j.type || 'INNER',
                    table: String(j.table ?? '').trim(),
                    alias: j.alias ? String(j.alias).trim() : null,
                    on: cleanOn
                };
            });

            const cleanSelect = (this.state.select || []).map(s => ({
                expr: String(s.expr ?? '*').trim() || '*',
                alias: s.alias ? String(s.alias).trim() : null,
                agg: s.agg ? String(s.agg).trim() : null
            }));

            const cleanWhere = (this.state.where || []).map(w => ({
                left: String(w.left ?? '').trim(),
                op: String(w.op ?? '=').trim(),
                value: w.value !== undefined && w.value !== null ? String(w.value) : '',
                logic: String(w.logic ?? 'AND').trim()
            }));

            return {
                version: '1.0',
                primary: {
                    base: {
                        table: String(this.state.base.table ?? '').trim(),
                        alias: this.state.base.alias ? String(this.state.base.alias).trim() : 't1'
                    },
                    joins: cleanJoins,
                    select: cleanSelect,
                    where: cleanWhere,
                    group_by: this.state.group_by || [],
                    order_by: this.state.order_by || [],
                    limit: this.state.limit
                },
                unions: this.state.unions || [],
                raw_blocks: this.state.raw_blocks || []
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
