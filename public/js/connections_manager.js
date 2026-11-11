/**
 * DataMind Studio - Gestor Dinámico de Bases de Datos y Vectorización Selectiva
 * Soporta navegación en 3 paneles monolíticos, pre-selección de esquemas y modal de estructura de tabla.
 */

(function () {
    'use strict';

    let currentConnectionId = null;
    let currentConnectionName = '';
    let allDetectedTables = [];
    let selectedTableNames = new Set();
    let currentSchemaFilter = 'all';
    let testedSchemasList = [];

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Paneles Principales
    const panelNewConnection = document.getElementById('panelNewConnection');
    const panelSavedConnections = document.getElementById('panelSavedConnections');
    const panelTablesExplorer = document.getElementById('panelTablesExplorer');

    // Elementos de Formulario de Conexión
    const connEngine = document.getElementById('connEngine');
    const connPort = document.getElementById('connPort');
    const btnTestConnection = document.getElementById('btnTestConnection');
    const btnSaveConnection = document.getElementById('btnSaveConnection');
    const testResultBanner = document.getElementById('testResultBanner');
    const detectedSchemasContainer = document.getElementById('detectedSchemasContainer');
    const schemasCheckboxGrid = document.getElementById('schemasCheckboxGrid');

    // Elementos del Explorador de Tablas
    const explorerTitle = document.getElementById('explorerTitle');
    const explorerSubtitle = document.getElementById('explorerSubtitle');
    const explorerStatsBadge = document.getElementById('explorerStatsBadge');
    const explorerToolbar = document.getElementById('explorerToolbar');
    const tablesEmptyPlaceholder = document.getElementById('tablesEmptyPlaceholder');
    const tablesGridWrapper = document.getElementById('tablesGridWrapper');
    const tablesListBody = document.getElementById('tablesListBody');
    const tableSearchInput = document.getElementById('tableSearchInput');
    const schemaFilterSelect = document.getElementById('schemaFilterSelect');

    const btnVectorizeSelected = document.getElementById('btnVectorizeSelected');
    const btnVectorizeText = document.getElementById('btnVectorizeText');
    const chkHeaderAll = document.getElementById('chkHeaderAll');
    const selectedCountBadge = document.getElementById('selectedCountBadge');
    const btnViewTableStructure = document.getElementById('btnViewTableStructure');

    // Modal de Estructura de Tabla
    const modalTableStructure = document.getElementById('modalTableStructure');
    const modalTableTitle = document.getElementById('modalTableTitle');
    const modalTableSubtitle = document.getElementById('modalTableSubtitle');
    const modalColCount = document.getElementById('modalColCount');
    const modalFkCount = document.getElementById('modalFkCount');
    const modalIdxCount = document.getElementById('modalIdxCount');
    const modalColumnsBody = document.getElementById('modalColumnsBody');
    const modalFkBody = document.getElementById('modalFkBody');
    const modalFkWrapper = document.getElementById('modalFkWrapper');
    const modalFkEmpty = document.getElementById('modalFkEmpty');
    const modalIdxBody = document.getElementById('modalIdxBody');
    const modalIdxWrapper = document.getElementById('modalIdxWrapper');
    const modalIdxEmpty = document.getElementById('modalIdxEmpty');

    document.addEventListener('DOMContentLoaded', function () {
        initNavigation();
        initFormEvents();
        initExplorerEvents();
        initModalEvents();
        initEditModalEvents();
        bindConnectionCardsEvents();

        // Cargar tablas de la conexión activa inicial si existe
        const firstCard = document.querySelector('.connection-card') || document.querySelector('.quick-conn-card');
        if (firstCard) {
            const firstId = firstCard.getAttribute('data-connection-id');
            const firstName = firstCard.getAttribute('data-connection-name');
            if (firstId) {
                loadConnectionTables(firstId, firstName);
            }
        }
    });

    /* =========================================================================
       1. NAVEGACIÓN ENTRE PANELES (Nueva Conexión, Bases Guardadas, Esquemas)
       ========================================================================= */
    function showPanel(panelName) {
        if (!panelNewConnection || !panelSavedConnections || !panelTablesExplorer) return;

        panelNewConnection.classList.add('hidden');
        panelSavedConnections.classList.add('hidden');
        panelTablesExplorer.classList.add('hidden');

        if (panelName === 'new') {
            panelNewConnection.classList.remove('hidden');
        } else if (panelName === 'saved') {
            panelSavedConnections.classList.remove('hidden');
        } else {
            panelTablesExplorer.classList.remove('hidden');
        }
    }

    function initNavigation() {
        // Enlaces del sidebar dinamico generados por EcosystemSidebar
        document.querySelectorAll('a[href="#connectionForm"], button#connectionForm').forEach(el => {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                showPanel('new');
            });
        });

        document.querySelectorAll('a[href="#connectionsContainer"], button#connectionsContainer').forEach(el => {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                showPanel('saved');
            });
        });

        document.querySelectorAll('a[href="#tableExplorerPanel"], button#tableExplorerPanel, a[href="#tablesExplorerContainer"]').forEach(el => {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                showPanel('explorer');
            });
        });

        document.getElementById('btnSidebarOpenNewConn')?.addEventListener('click', () => showPanel('new'));
        document.querySelectorAll('.btn-open-new-conn').forEach(b => b.addEventListener('click', () => showPanel('new')));
        document.querySelectorAll('.btn-switch-to-explorer').forEach(b => b.addEventListener('click', () => showPanel('explorer')));

        // Hash inicial de URL
        if (window.location.hash === '#connectionForm') {
            showPanel('new');
        } else if (window.location.hash === '#connectionsContainer') {
            showPanel('saved');
        }
    }

    /* =========================================================================
       2. EVENTOS DEL FORMULARIO Y ANÁLISIS PREVIO DE ESQUEMAS
       ========================================================================= */
    function initFormEvents() {
        if (connEngine && connPort) {
            connEngine.addEventListener('change', function () {
                if (this.value === 'postgresql' && connPort.value === '3306') {
                    connPort.value = '5432';
                } else if (this.value === 'mariadb' && connPort.value === '5432') {
                    connPort.value = '3306';
                }
            });
        }

        if (btnTestConnection) {
            btnTestConnection.addEventListener('click', handleTestConnection);
        }

        if (btnSaveConnection) {
            btnSaveConnection.addEventListener('click', handleSaveConnection);
        }

        document.getElementById('btnSelectAllSchemas')?.addEventListener('click', function () {
            document.querySelectorAll('.chk-schema-detected').forEach(chk => chk.checked = true);
        });

        document.getElementById('btnUnselectAllSchemas')?.addEventListener('click', function () {
            document.querySelectorAll('.chk-schema-detected').forEach(chk => chk.checked = false);
        });
    }

    function readFormCredentials() {
        return {
            name: document.getElementById('connName')?.value.trim() || '',
            engine: document.getElementById('connEngine')?.value || 'postgresql',
            host: document.getElementById('connHost')?.value.trim() || '',
            port: parseInt(document.getElementById('connPort')?.value || '5432', 10),
            database_name: document.getElementById('connDatabase')?.value.trim() || '',
            username: document.getElementById('connUsername')?.value.trim() || '',
            password: document.getElementById('connPassword')?.value || '',
            set_active: true
        };
    }

    async function handleTestConnection() {
        const creds = readFormCredentials();
        if (!creds.host || !creds.database_name || !creds.username) {
            if (window.toastr) toastr.warning('Completa Host, Base de Datos y Usuario.');
            return;
        }

        btnTestConnection.disabled = true;
        btnTestConnection.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Analizando BD...`;
        testResultBanner.classList.add('hidden');
        detectedSchemasContainer.classList.add('hidden');

        try {
            const response = await fetch('/connections/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(creds)
            });

            const json = await response.json();
            if (response.ok && json.success) {
                const info = json.data;
                testedSchemasList = info.schemas_detected || [];

                testResultBanner.className = 'p-3 rounded-none text-xs border bg-emerald-50 border-emerald-200 text-emerald-800';
                testResultBanner.innerHTML = `
                    <div class="font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> ¡Conexión Verificada! (${info.latency_ms} ms)
                    </div>
                    <div class="mt-1 text-[11px]">
                        Se detectaron <strong>${info.tables_detected} tablas</strong> en <code>${escapeHtml(info.database_name)}</code> distribuidas en <strong>${testedSchemasList.length} esquemas</strong>.
                    </div>
                `;
                testResultBanner.classList.remove('hidden');

                // Renderizar esquemas detectados con checkboxes
                if (testedSchemasList.length > 0 && schemasCheckboxGrid) {
                    schemasCheckboxGrid.innerHTML = testedSchemasList.map(s => `
                        <label class="flex items-center gap-2 p-1.5 border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer text-xs">
                            <input type="checkbox" class="chk-schema-detected h-3.5 w-3.5 rounded-none accent-[#0476D9]"
                                value="${escapeHtml(s.name)}" checked>
                            <span class="font-mono font-bold text-slate-800 truncate">${escapeHtml(s.name)}</span>
                            <span class="ml-auto text-[10px] text-slate-500 font-semibold">(${s.tables_count} tablas)</span>
                        </label>
                    `).join('');
                    detectedSchemasContainer.classList.remove('hidden');
                }

                if (window.toastr) toastr.success(`BD analizada: ${info.tables_detected} tablas encontradas.`, 'DataMind Studio');
            } else {
                throw new Error(json.message || 'Fallo al conectar con el servidor de base de datos.');
            }
        } catch (err) {
            testResultBanner.className = 'p-3 rounded-none text-xs border bg-red-50 border-red-200 text-red-700';
            testResultBanner.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1"></i> ${escapeHtml(err.message)}`;
            testResultBanner.classList.remove('hidden');
            if (window.toastr) toastr.error(err.message, 'Error de Conexión');
        } finally {
            btnTestConnection.disabled = false;
            btnTestConnection.innerHTML = `<i class="fa-solid fa-bolt"></i> Analizar y Probar BD`;
        }
    }

    async function handleSaveConnection() {
        const creds = readFormCredentials();
        if (!creds.name || !creds.host || !creds.database_name || !creds.username) {
            if (window.toastr) toastr.warning('Completa Alias, Host, Base de Datos y Usuario.');
            return;
        }

        // Obtener esquemas marcados
        const selectedSchemas = [];
        document.querySelectorAll('.chk-schema-detected:checked').forEach(chk => {
            selectedSchemas.push(chk.value);
        });

        if (testedSchemasList.length > 0 && selectedSchemas.length === 0) {
            if (window.toastr) toastr.warning('Debes seleccionar al menos un esquema para incorporar.');
            return;
        }

        creds.selected_schemas = selectedSchemas;

        btnSaveConnection.disabled = true;
        btnSaveConnection.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Guardando...`;

        try {
            const response = await fetch('/connections', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(creds)
            });

            const json = await response.json();
            if (response.ok && json.success) {
                const created = json.data;
                if (window.toastr) {
                    toastr.success(`Base de datos "${created.name}" guardada y activada.`, 'DataMind Studio');
                }
                showPanel('explorer');
                await refreshConnectionsList();
                await loadConnectionTables(created.id, created.name);
            } else {
                throw new Error(json.message || 'No se pudo guardar la conexión.');
            }
        } catch (err) {
            if (window.toastr) toastr.error(err.message, 'Error al Guardar');
        } finally {
            btnSaveConnection.disabled = false;
            btnSaveConnection.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> Guardar Conexión`;
        }
    }

    /* =========================================================================
       3. EXPLORADOR DE ESQUEMAS, TABLAS Y DATA TABLE ESTÁTICO
       ========================================================================= */
    function initExplorerEvents() {
        if (btnVectorizeSelected) {
            btnVectorizeSelected.addEventListener('click', handleVectorizeSelected);
        }

        if (schemaFilterSelect) {
            schemaFilterSelect.addEventListener('change', function () {
                currentSchemaFilter = this.value;
                renderTablesRows(tableSearchInput?.value.trim().toLowerCase() || '');
            });
        }

        if (tableSearchInput) {
            tableSearchInput.addEventListener('input', function () {
                renderTablesRows(this.value.trim().toLowerCase());
            });
        }

        if (chkHeaderAll) {
            chkHeaderAll.addEventListener('change', function () {
                toggleVisibleCheckboxes(this.checked);
            });
        }

        document.getElementById('btnSelectAllVisible')?.addEventListener('click', () => toggleVisibleCheckboxes(true));
        document.getElementById('btnClearSelection')?.addEventListener('click', () => {
            selectedTableNames.clear();
            if (chkHeaderAll) chkHeaderAll.checked = false;
            renderTablesRows(tableSearchInput?.value.trim().toLowerCase() || '');
            updateSelectionState();
        });

        document.getElementById('btnSelectUnvectorized')?.addEventListener('click', () => {
            selectedTableNames.clear();
            allDetectedTables.forEach(t => {
                if (!t.is_vectorized) {
                    selectedTableNames.add(t.table_name);
                }
            });
            renderTablesRows(tableSearchInput?.value.trim().toLowerCase() || '');
            updateSelectionState();
        });

        if (btnViewTableStructure) {
            btnViewTableStructure.addEventListener('click', openSelectedTableStructureModal);
        }
    }

    function bindConnectionCardsEvents() {
        document.querySelectorAll('.btn-inspect-tables').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                showPanel('explorer');
                loadConnectionTables(this.getAttribute('data-id'), this.getAttribute('data-name'));
            });
        });

        document.querySelectorAll('.btn-activate-conn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                handleActivateConnection(this.getAttribute('data-id'));
            });
        });

                document.querySelectorAll('.btn-edit-conn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                openEditConnectionModal(this.getAttribute('data-id'));
            });
        });
        document.querySelectorAll('.btn-delete-conn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                handleDeleteConnection(this.getAttribute('data-id'), this.getAttribute('data-name'));
            });
        });

        document.querySelectorAll('.quick-conn-card').forEach(card => {
            card.addEventListener('click', function () {
                showPanel('explorer');
                loadConnectionTables(
                    this.getAttribute('data-connection-id'),
                    this.getAttribute('data-connection-name')
                );
            });
        });
    }

    async function loadConnectionTables(connectionId, connectionName) {
        if (!connectionId) return;
        currentConnectionId = connectionId;
        currentConnectionName = connectionName || 'Base de Datos';
        selectedTableNames.clear();
        updateSelectionState();

        explorerTitle.textContent = `Analizando: ${currentConnectionName}...`;
        tablesEmptyPlaceholder.classList.remove('hidden');
        tablesEmptyPlaceholder.innerHTML = `
            <div class="py-12 text-center">
                <i class="fa-solid fa-circle-notch fa-spin text-2xl text-datamind-primary mb-2"></i>
                <p class="text-xs font-semibold text-slate-600">Inspeccionando tablas y relaciones...</p>
            </div>
        `;
        tablesGridWrapper.classList.add('hidden');
        explorerToolbar.classList.add('hidden');

        try {
            const response = await fetch(`/connections/${connectionId}/tables`, {
                headers: { 'Accept': 'application/json' }
            });
            const json = await response.json();

            if (!response.ok || !json.success) {
                throw new Error(json.message || 'No se pudieron obtener las tablas.');
            }

            const payload = json.data;
            allDetectedTables = payload.tables || [];

            explorerTitle.textContent = `Tablas en ${payload.connection.name} (${payload.connection.database_name})`;
            explorerSubtitle.textContent = `Motor ${payload.connection.engine.toUpperCase()} en ${payload.connection.host}:${payload.connection.port} — Selecciona tablas para pgvector o examina su estructura.`;
            explorerStatsBadge.textContent = `${payload.total_tables} tablas • ${payload.vectorized_count} vectorizadas`;
            explorerStatsBadge.classList.remove('hidden');

            // Poblar dropdown de esquemas
            if (schemaFilterSelect) {
                const schemaSet = new Set();
                allDetectedTables.forEach(t => {
                    const s = t.schema_name || 'public';
                    schemaSet.add(s);
                });
                const schemas = Array.from(schemaSet).sort();
                schemaFilterSelect.innerHTML = '<option value="all">Todos los Esquemas (' + schemas.length + ')</option>' +
                    schemas.map(s => {
                        const count = allDetectedTables.filter(x => (x.schema_name || 'public') === s).length;
                        return `<option value="${escapeHtml(s)}">${escapeHtml(s)} (${count})</option>`;
                    }).join('');
                currentSchemaFilter = 'all';
            }

            tablesEmptyPlaceholder.classList.add('hidden');
            explorerToolbar.classList.remove('hidden');
            tablesGridWrapper.classList.remove('hidden');

            renderTablesRows(tableSearchInput?.value.trim().toLowerCase() || '');
        } catch (err) {
            tablesEmptyPlaceholder.innerHTML = `
                <div class="py-12 text-center text-red-600">
                    <i class="fa-solid fa-triangle-exclamation text-2xl mb-2"></i>
                    <p class="text-xs font-semibold">${escapeHtml(err.message)}</p>
                </div>
            `;
        }
    }

    function renderTablesRows(filterText = '') {
        if (!tablesListBody) return;

        const filtered = allDetectedTables.filter(t => {
            const schema = t.schema_name || 'public';
            if (currentSchemaFilter !== 'all' && schema !== currentSchemaFilter) {
                return false;
            }

            if (!filterText) return true;
            return t.table_name.toLowerCase().includes(filterText) ||
                   schema.toLowerCase().includes(filterText) ||
                   (t.column_names_preview || '').toLowerCase().includes(filterText);
        });

        if (filtered.length === 0) {
            tablesListBody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                        No se encontraron tablas con el filtro "${escapeHtml(filterText)}".
                    </td>
                </tr>
            `;
            return;
        }

        tablesListBody.innerHTML = filtered.map(t => {
            const isChecked = selectedTableNames.has(t.table_name);
            const schema = t.schema_name || 'public';
            const relatedList = Array.isArray(t.related_tables) ? t.related_tables : [];
            const relationBadge = relatedList.length > 0
                ? `<div class="mt-0.5 flex flex-wrap items-center gap-1">
                       <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-none text-[9px] font-semibold bg-datamind-light/30 text-datamind-dark border border-datamind-light/80"
                             title="Relaciones JOIN: ${escapeHtml(relatedList.join(', '))}">
                           <i class="fa-solid fa-diagram-project text-[8px] text-datamind-primary"></i>
                           JOIN: ${escapeHtml(relatedList.slice(0, 3).join(', '))}${relatedList.length > 3 ? ` (+${relatedList.length - 3})` : ''}
                       </span>
                   </div>`
                : '';

            const schemaBadge = `<span class="inline-block px-1.5 py-0.2 mr-1 rounded-none text-[9px] font-bold font-mono uppercase bg-slate-100 text-slate-600 border border-slate-200">
                ${escapeHtml(schema)}
            </span>`;

            const statusBadge = t.is_vectorized
                ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-none text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                       <i class="fa-solid fa-check text-[9px]"></i> Vectorizada
                   </span>`
                : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-none text-[10px] font-medium bg-slate-100 text-slate-500">
                       Pendiente
                   </span>`;

            return `
                <tr class="table-row-item hover:bg-slate-50/70 transition cursor-pointer ${isChecked ? 'bg-slate-50' : ''}"
                    data-table-name="${escapeHtml(t.table_name)}">
                    <td class="py-2 px-3 text-center">
                        <input type="checkbox" class="chk-table-item h-3.5 w-3.5 rounded-none accent-[#0476D9] cursor-pointer"
                            value="${escapeHtml(t.table_name)}" ${isChecked ? 'checked' : ''}>
                    </td>
                    <td class="py-2 px-3">
                        <div class="font-mono font-bold text-slate-900 flex items-center gap-1 text-xs">
                            ${schemaBadge}
                            <span>${escapeHtml(t.table_name)}</span>
                        </div>
                        ${relationBadge}
                    </td>
                    <td class="py-2 px-3 text-center">
                        <span class="px-2 py-0.5 rounded-none bg-slate-100 text-slate-700 font-semibold text-[10px]">
                            ${t.columns_count} cols
                        </span>
                    </td>
                    <td class="py-2 px-3 text-slate-500 font-mono text-[11px] max-w-md truncate" title="${escapeHtml(t.column_names_preview)}">
                        ${escapeHtml(t.column_names_preview)}
                    </td>
                    <td class="py-2 px-3 text-center">${statusBadge}</td>
                </tr>
            `;
        }).join('');

        // Eventos en fila y checkbox
        tablesListBody.querySelectorAll('.table-row-item').forEach(row => {
            row.addEventListener('click', function (e) {
                if (e.target.tagName === 'INPUT') return;
                const chk = this.querySelector('.chk-table-item');
                if (chk) {
                    chk.checked = !chk.checked;
                    toggleTableSelection(chk.value, chk.checked, this);
                }
            });
        });

        tablesListBody.querySelectorAll('.chk-table-item').forEach(chk => {
            chk.addEventListener('change', function () {
                const row = this.closest('.table-row-item');
                toggleTableSelection(this.value, this.checked, row);
            });
        });
    }

    function toggleTableSelection(tableName, isChecked, rowElement) {
        if (isChecked) {
            selectedTableNames.add(tableName);
            rowElement?.classList.add('bg-slate-50');
        } else {
            selectedTableNames.delete(tableName);
            rowElement?.classList.remove('bg-slate-50');
        }
        updateSelectionState();
    }

    function toggleVisibleCheckboxes(checked) {
        if (!tablesListBody) return;
        tablesListBody.querySelectorAll('.chk-table-item').forEach(chk => {
            chk.checked = checked;
            const row = chk.closest('.table-row-item');
            if (checked) {
                selectedTableNames.add(chk.value);
                row?.classList.add('bg-slate-50');
            } else {
                selectedTableNames.delete(chk.value);
                row?.classList.remove('bg-slate-50');
            }
        });
        updateSelectionState();
    }

    function updateSelectionState() {
        const count = selectedTableNames.size;

        if (selectedCountBadge) {
            selectedCountBadge.textContent = `${count} ${count === 1 ? 'tabla' : 'tablas'}`;
        }

        if (btnVectorizeSelected && btnVectorizeText) {
            if (count > 0) {
                btnVectorizeSelected.disabled = false;
                btnVectorizeSelected.classList.remove('opacity-50', 'cursor-not-allowed');
                btnVectorizeText.textContent = `Vectorizar Seleccionadas (${count})`;
            } else {
                btnVectorizeSelected.disabled = true;
                btnVectorizeSelected.classList.add('opacity-50', 'cursor-not-allowed');
                btnVectorizeText.textContent = 'Vectorizar Seleccionadas (0)';
            }
        }

        // BOTÓN DE ESTRUCTURA: Habilitado ESTRICTAMENTE cuando count === 1
        if (btnViewTableStructure) {
            if (count === 1) {
                btnViewTableStructure.disabled = false;
                btnViewTableStructure.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-slate-200', 'text-slate-400');
                btnViewTableStructure.classList.add('bg-white', 'text-datamind-primary', 'hover:bg-slate-100', 'cursor-pointer');
            } else {
                btnViewTableStructure.disabled = true;
                btnViewTableStructure.classList.add('opacity-50', 'cursor-not-allowed', 'bg-slate-200', 'text-slate-400');
                btnViewTableStructure.classList.remove('bg-white', 'text-datamind-primary', 'hover:bg-slate-100', 'cursor-pointer');
            }
        }
    }

    /* =========================================================================
       4. MODAL MONOLÍTICO DE ESTRUCTURA COMPLETA DE TABLA
       ========================================================================= */
    function initModalEvents() {
        document.getElementById('btnCloseTableStructureModal')?.addEventListener('click', closeTableStructureModal);
        document.getElementById('btnDismissModal')?.addEventListener('click', closeTableStructureModal);

        modalTableStructure?.addEventListener('click', function (e) {
            if (e.target === this) closeTableStructureModal();
        });

        document.querySelectorAll('.tab-structure-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const targetTab = this.getAttribute('data-tab');
                document.querySelectorAll('.tab-structure-btn').forEach(b => {
                    b.classList.remove('border-b-2', 'border-datamind-primary', 'text-datamind-primary', 'font-bold');
                    b.classList.add('text-slate-500', 'font-medium');
                });
                this.classList.add('border-b-2', 'border-datamind-primary', 'text-datamind-primary', 'font-bold');
                this.classList.remove('text-slate-500', 'font-medium');

                document.querySelectorAll('.tab-structure-content').forEach(c => c.classList.add('hidden'));
                document.getElementById(targetTab)?.classList.remove('hidden');
            });
        });
    }

    function openSelectedTableStructureModal() {
        if (selectedTableNames.size !== 1) return;
        const targetTableName = Array.from(selectedTableNames)[0];
        const table = allDetectedTables.find(t => t.table_name === targetTableName);
        if (!table) return;

        modalTableTitle.textContent = `${table.schema_name || 'public'}.${table.table_name}`;
        modalTableSubtitle.textContent = `Diccionario de Datos • ${table.columns_count} columnas • Llave Primaria: ${table.primary_key || 'id'}`;

        const cols = table.columns || [];
        const fks = table.foreign_keys || [];
        const idxs = table.indexes || [];

        modalColCount.textContent = cols.length;
        modalFkCount.textContent = fks.length;
        modalIdxCount.textContent = idxs.length;

        // 1. Columnas
        modalColumnsBody.innerHTML = cols.map(c => `
            <tr class="hover:bg-slate-50 transition">
                <td class="py-1.5 px-3 font-bold text-slate-800">
                    ${escapeHtml(c.name)}
                    ${(c.name === table.primary_key) ? '<span class="ml-1 text-[9px] px-1 py-0.2 bg-amber-100 text-amber-800 font-mono">PK</span>' : ''}
                </td>
                <td class="py-1.5 px-3 text-slate-600">${escapeHtml(c.type || 'varchar')}</td>
                <td class="py-1.5 px-3 text-center">
                    ${c.nullable ? '<span class="text-emerald-600">SÍ</span>' : '<span class="text-slate-400 font-bold">NO</span>'}
                </td>
                <td class="py-1.5 px-3 text-slate-500 font-sans text-xs">${escapeHtml(c.comment || '—')}</td>
            </tr>
        `).join('');

        // 2. Llaves Foráneas
        if (fks.length > 0) {
            modalFkEmpty.classList.add('hidden');
            modalFkWrapper.classList.remove('hidden');
            modalFkBody.innerHTML = fks.map(fk => `
                <tr class="hover:bg-slate-50 transition">
                    <td class="py-1.5 px-3 font-bold text-slate-800">${escapeHtml(fk.column)}</td>
                    <td class="py-1.5 px-3 text-datamind-primary font-bold">${escapeHtml(fk.referenced_table)}</td>
                    <td class="py-1.5 px-3 text-slate-700">${escapeHtml(fk.referenced_column)}</td>
                    <td class="py-1.5 px-3 text-slate-500 font-sans text-xs">${escapeHtml((fk.descriptive_columns || []).join(', ') || '—')}</td>
                </tr>
            `).join('');
        } else {
            modalFkEmpty.classList.remove('hidden');
            modalFkWrapper.classList.add('hidden');
        }

        // 3. Índices
        if (idxs.length > 0) {
            modalIdxEmpty.classList.add('hidden');
            modalIdxWrapper.classList.remove('hidden');
            modalIdxBody.innerHTML = idxs.map(idx => `
                <tr class="hover:bg-slate-50 transition">
                    <td class="py-1.5 px-3 font-bold text-slate-800">${escapeHtml(idx.name)}</td>
                    <td class="py-1.5 px-3 text-slate-600 text-[10px]">${escapeHtml(idx.definition || '—')}</td>
                </tr>
            `).join('');
        } else {
            modalIdxEmpty.classList.remove('hidden');
            modalIdxWrapper.classList.add('hidden');
        }

        modalTableStructure.classList.remove('hidden');
    }

    function closeTableStructureModal() {
        modalTableStructure?.classList.add('hidden');
    }

    /* =========================================================================
       5. ACCIONES DE VECTORIZACIÓN Y CRUD
       ========================================================================= */
    async function handleVectorizeSelected() {
        if (!currentConnectionId || selectedTableNames.size === 0) return;
        const tablesArray = Array.from(selectedTableNames);

        btnVectorizeSelected.disabled = true;
        btnVectorizeSelected.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Vectorizando ${tablesArray.length}...`;

        try {
            const response = await fetch(`/connections/${currentConnectionId}/vectorize`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ tables: tablesArray })
            });

            const json = await response.json();
            if (!response.ok || !json.success) {
                throw new Error(json.message || 'Ocurrió un error al vectorizar las tablas.');
            }

            const result = json.data;
            if (window.Swal) {
                Swal.fire({
                    title: '¡Tablas Vectorizadas en pgvector!',
                    html: `El asistente ahora conoce <strong>${result.tables_vectorized_now} tablas</strong> de <code>${escapeHtml(result.database_name)}</code> (${result.total_synced_tables} totales en esta conexión).`,
                    icon: 'success',
                    confirmButtonColor: '#0476D9',
                    confirmButtonText: 'Excelente'
                });
            } else if (window.toastr) {
                toastr.success(`${result.tables_vectorized_now} tablas vectorizadas correctamente.`);
            }

            await refreshConnectionsList();
            await loadConnectionTables(currentConnectionId, currentConnectionName);
        } catch (err) {
            if (window.toastr) toastr.error(err.message, 'Error en Vectorización');
        } finally {
            updateSelectionState();
        }
    }

    async function handleActivateConnection(connectionId) {
        try {
            const response = await fetch(`/connections/${connectionId}/activate`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            const json = await response.json();
            if (response.ok && json.success) {
                if (window.toastr) toastr.success(`Conexión "${json.data.name}" activada para el Chat.`);
                await refreshConnectionsList();
            }
        } catch (err) {
            if (window.toastr) toastr.error('No se pudo activar la conexión.');
        }
    }

    async function handleDeleteConnection(connectionId, connectionName) {
        const proceed = window.Swal
            ? (await Swal.fire({
                title: `¿Eliminar "${connectionName}"?`,
                text: 'Se eliminará la conexión y sus esquemas vectorizados en pgvector.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#4B89BF',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            })).isConfirmed
            : confirm(`¿Eliminar la conexión ${connectionName}?`);

        if (!proceed) return;

        try {
            const response = await fetch(`/connections/${connectionId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            if (response.ok) {
                if (window.toastr) toastr.info('Conexión eliminada correctamente.');
                await refreshConnectionsList();
            }
        } catch (err) {
            if (window.toastr) toastr.error('Error al eliminar la conexión.');
        }
    }

    async function refreshConnectionsList() {
        window.location.reload();
    }

        /* =========================================================================
       6. MODAL DE EDICIÓN DE CONEXIÓN Y ESQUEMAS
       ========================================================================= */
    let cachedConnections = [];

    async function fetchAllConnections() {
        try {
            const res = await fetch('/connections/list');
            const json = await res.json();
            if (json.success) {
                cachedConnections = json.connections || [];
            }
        } catch (e) {
            console.error('Error fetching connections:', e);
        }
    }

    async function openEditConnectionModal(connectionId) {
        if (!cachedConnections || cachedConnections.length === 0) {
            await fetchAllConnections();
        }
        const conn = cachedConnections.find(c => c.id === connectionId);
        if (!conn) {
            if (window.toastr) toastr.error('No se pudo encontrar la información de la conexión.');
            return;
        }

        document.getElementById('editConnId').value = conn.id;
        document.getElementById('editConnName').value = conn.name;
        document.getElementById('editConnEngine').value = conn.engine;
        document.getElementById('editConnPort').value = conn.port;
        document.getElementById('editConnHost').value = conn.host;
        document.getElementById('editConnDatabase').value = conn.database_name;
        document.getElementById('editConnUsername').value = conn.username;
        document.getElementById('editConnPassword').value = '';

        const grid = document.getElementById('editSchemasCheckboxGrid');
        grid.innerHTML = '<div class="col-span-full py-4 text-center text-slate-400"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Detectando esquemas disponibles en la base de datos...</div>';
        document.getElementById('modalEditConnection').classList.remove('hidden');

        await introspectSchemasForEdit(conn);
    }

    async function introspectSchemasForEdit(conn) {
        const grid = document.getElementById('editSchemasCheckboxGrid');
        const banner = document.getElementById('editTestResultBanner');
        banner.classList.add('hidden');

        try {
            // Consultar directamente los esquemas usando las credenciales guardadas en el servidor
            let schemasList = [];
            let activeSchemasList = conn.selected_schemas || [];

            const res = await fetch(`/connections/${conn.id}/schemas`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const json = await res.json();
            if (res.ok && json.success) {
                schemasList = json.data.schemas_detected || [];
                if (json.data.selected_schemas) {
                    activeSchemasList = json.data.selected_schemas;
                }
            } else {
                // Fallback de contingencia: extraer esquemas de las tablas inspeccionadas
                const tablesRes = await fetch(`/connections/${conn.id}/tables`, {
                    headers: { 'Accept': 'application/json' }
                });
                const tablesJson = await tablesRes.json();
                if (tablesJson.success && tablesJson.data) {
                    const schemaCounts = {};
                    (tablesJson.data.tables || []).forEach(t => {
                        const s = t.schema_name || 'public';
                        schemaCounts[s] = (schemaCounts[s] || 0) + 1;
                    });
                    schemasList = Object.keys(schemaCounts).map(s => ({ name: s, tables_count: schemaCounts[s] }));
                }
            }

            const currentActiveSchemas = new Set(activeSchemasList || []);
            const hasExplicitSchemas = (activeSchemasList && activeSchemasList.length > 0);

            if (schemasList.length === 0) {
                grid.innerHTML = '<div class="col-span-full py-3 text-center text-slate-400">No se encontraron múltiples esquemas; se utiliza el esquema por defecto.</div>';
                return;
            }

            grid.innerHTML = schemasList.map(s => {
                const isChecked = hasExplicitSchemas ? currentActiveSchemas.has(s.name) : true;
                return `
                    <label class="flex items-center gap-2 p-1.5 border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer text-xs">
                        <input type="checkbox" class="chk-edit-schema h-3.5 w-3.5 rounded-none accent-[#0476D9]"
                            value="${escapeHtml(s.name)}" ${isChecked ? 'checked' : ''}>
                        <span class="font-mono font-bold text-slate-800 truncate">${escapeHtml(s.name)}</span>
                        <span class="ml-auto text-[10px] text-slate-500 font-semibold">(${s.tables_count} tablas)</span>
                    </label>
                `;
            }).join('');
        } catch (err) {
            grid.innerHTML = `<div class="col-span-full py-3 text-center text-red-500">Error al detectar esquemas: ${escapeHtml(err.message)}</div>`;
        }
    }

    async function handleSaveEditedConnection() {
        const id = document.getElementById('editConnId').value;
        const name = document.getElementById('editConnName').value.trim();
        const engine = document.getElementById('editConnEngine').value;
        const port = parseInt(document.getElementById('editConnPort').value, 10);
        const host = document.getElementById('editConnHost').value.trim();
        const database_name = document.getElementById('editConnDatabase').value.trim();
        const username = document.getElementById('editConnUsername').value.trim();
        const password = document.getElementById('editConnPassword').value;

        const selectedSchemas = [];
        document.querySelectorAll('.chk-edit-schema:checked').forEach(chk => {
            selectedSchemas.push(chk.value);
        });

        const btn = document.getElementById('btnSaveEditedConnection');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Guardando...';

        try {
            const payload = {
                name, engine, port, host, database_name, username,
                selected_schemas: selectedSchemas
            };
            if (password) {
                payload.password = password;
            }

            const res = await fetch(`/connections/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const json = await res.json();
            if (res.ok && json.success) {
                if (window.toastr) toastr.success(`Conexión "${name}" actualizada correctamente.`, 'DataMind Studio');
                document.getElementById('modalEditConnection').classList.add('hidden');
                await refreshConnectionsList();
            } else {
                throw new Error(json.message || 'Error al actualizar la conexión.');
            }
        } catch (err) {
            if (window.toastr) toastr.error(err.message, 'Error de Actualización');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i> Actualizar Conexión';
        }
    }

    function initEditModalEvents() {
        fetchAllConnections();
        document.getElementById('btnCloseEditConnModal')?.addEventListener('click', () => {
            document.getElementById('modalEditConnection')?.classList.add('hidden');
        });
        document.getElementById('btnCancelEditConn')?.addEventListener('click', () => {
            document.getElementById('modalEditConnection')?.classList.add('hidden');
        });
        document.getElementById('btnSaveEditedConnection')?.addEventListener('click', handleSaveEditedConnection);
        document.getElementById('btnRefreshEditSchemas')?.addEventListener('click', () => {
            const id = document.getElementById('editConnId').value;
            const conn = cachedConnections.find(c => c.id === id);
            if (conn) introspectSchemasForEdit(conn);
        });
    }

    function escapeHtml(string) {
        if (!string) return '';
        return String(string)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
