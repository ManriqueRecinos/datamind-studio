/**
 * DataMind Studio - Gestor Dinámico de Bases de Datos y Vectorización Selectiva
 * Soporta PostgreSQL y MariaDB/MySQL con exploración interactiva de tablas y pgvector.
 */

(function () {
    'use strict';

    let currentConnectionId = null;
    let currentConnectionName = '';
    let allDetectedTables = [];
    let selectedTableNames = new Set();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Elementos del DOM
    const connEngine = document.getElementById('connEngine');
    const connPort = document.getElementById('connPort');
    const btnTestConnection = document.getElementById('btnTestConnection');
    const btnSaveConnection = document.getElementById('btnSaveConnection');
    const testResultBanner = document.getElementById('testResultBanner');

    const explorerTitle = document.getElementById('explorerTitle');
    const explorerSubtitle = document.getElementById('explorerSubtitle');
    const explorerStatsBadge = document.getElementById('explorerStatsBadge');
    const explorerToolbar = document.getElementById('explorerToolbar');
    const tablesEmptyPlaceholder = document.getElementById('tablesEmptyPlaceholder');
    const tablesGridWrapper = document.getElementById('tablesGridWrapper');
    const tablesListBody = document.getElementById('tablesListBody');
    const tableSearchInput = document.getElementById('tableSearchInput');

    const btnVectorizeSelected = document.getElementById('btnVectorizeSelected');
    const btnVectorizeText = document.getElementById('btnVectorizeText');
    const chkHeaderAll = document.getElementById('chkHeaderAll');

    document.addEventListener('DOMContentLoaded', function () {
        // Cambiar puerto sugerido automáticamente al cambiar de motor
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

        if (btnVectorizeSelected) {
            btnVectorizeSelected.addEventListener('click', handleVectorizeSelected);
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
            updateVectorizeButtonState();
        });

        document.getElementById('btnSelectUnvectorized')?.addEventListener('click', () => {
            selectedTableNames.clear();
            allDetectedTables.forEach(t => {
                if (!t.is_vectorized) {
                    selectedTableNames.add(t.table_name);
                }
            });
            renderTablesRows(tableSearchInput?.value.trim().toLowerCase() || '');
            updateVectorizeButtonState();
        });

        bindConnectionCardsEvents();

        // Auto-cargar las tablas de la primera conexión activa si existe
        const firstCard = document.querySelector('.connection-card');
        if (firstCard) {
            const firstId = firstCard.getAttribute('data-connection-id');
            const firstName = firstCard.getAttribute('data-connection-name');
            if (firstId) {
                loadConnectionTables(firstId, firstName);
            }
        }
    });

    function bindConnectionCardsEvents() {
        document.querySelectorAll('.btn-inspect-tables').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                loadConnectionTables(this.getAttribute('data-id'), this.getAttribute('data-name'));
            });
        });

        document.querySelectorAll('.btn-activate-conn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                handleActivateConnection(this.getAttribute('data-id'));
            });
        });

        document.querySelectorAll('.btn-delete-conn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                handleDeleteConnection(this.getAttribute('data-id'), this.getAttribute('data-name'));
            });
        });

        document.querySelectorAll('.connection-card').forEach(card => {
            card.addEventListener('click', function () {
                loadConnectionTables(
                    this.getAttribute('data-connection-id'),
                    this.getAttribute('data-connection-name')
                );
            });
        });
    }

    function readFormCredentials() {
        return {
            name: document.getElementById('connName')?.value.trim() || '',
            engine: document.getElementById('connEngine')?.value || 'mariadb',
            host: document.getElementById('connHost')?.value.trim() || '',
            port: parseInt(document.getElementById('connPort')?.value || '3306', 10),
            database_name: document.getElementById('connDatabase')?.value.trim() || '',
            username: document.getElementById('connUsername')?.value.trim() || '',
            password: document.getElementById('connPassword')?.value || '',
            set_active: true
        };
    }

    async function handleTestConnection() {
        const creds = readFormCredentials();
        if (!creds.host || !creds.database_name || !creds.username) {
            if (window.toastr) toastr.warning('Completa Host, Base de Datos y Usuario para probar la conexión.');
            return;
        }

        btnTestConnection.disabled = true;
        btnTestConnection.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Probando...`;
        testResultBanner.classList.add('hidden');

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
                testResultBanner.className = 'p-3 rounded-xl text-xs border bg-emerald-50 border-emerald-200 text-emerald-800';
                testResultBanner.innerHTML = `
                    <div class="font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> ¡Conexión Exitosa! (${info.latency_ms} ms)
                    </div>
                    <div class="mt-1 text-[11px]">
                        Se detectaron <strong>${info.tables_detected} tablas</strong> en <code>${escapeHtml(info.database_name)}</code>.
                    </div>
                `;
                testResultBanner.classList.remove('hidden');
                if (window.toastr) toastr.success(`Conexión verificada: ${info.tables_detected} tablas detectadas.`, 'DataMind Studio');
            } else {
                throw new Error(json.message || 'Fallo al conectar con el servidor de base de datos.');
            }
        } catch (err) {
            testResultBanner.className = 'p-3 rounded-xl text-xs border bg-red-50 border-red-200 text-red-700';
            testResultBanner.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1"></i> ${escapeHtml(err.message)}`;
            testResultBanner.classList.remove('hidden');
            if (window.toastr) toastr.error(err.message, 'Error de Conexión');
        } finally {
            btnTestConnection.disabled = false;
            btnTestConnection.innerHTML = `<i class="fa-solid fa-bolt"></i> Probar Conexión`;
        }
    }

    async function handleSaveConnection() {
        const creds = readFormCredentials();
        if (!creds.name || !creds.host || !creds.database_name || !creds.username) {
            if (window.toastr) toastr.warning('Completa Alias, Host, Base de Datos y Usuario.');
            return;
        }

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
                await refreshConnectionsList();
                await loadConnectionTables(created.id, created.name);
            } else {
                throw new Error(json.message || 'No se pudo guardar la conexión.');
            }
        } catch (err) {
            if (window.toastr) toastr.error(err.message, 'Error al Guardar');
        } finally {
            btnSaveConnection.disabled = false;
            btnSaveConnection.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> Guardar y Analizar`;
        }
    }

    async function loadConnectionTables(connectionId, connectionName) {
        if (!connectionId) return;
        currentConnectionId = connectionId;
        currentConnectionName = connectionName || 'Base de Datos';
        selectedTableNames.clear();
        updateVectorizeButtonState();

        explorerTitle.textContent = `Analizando esquema de: ${currentConnectionName}...`;
        tablesEmptyPlaceholder.classList.remove('hidden');
        tablesEmptyPlaceholder.innerHTML = `
            <div class="py-12 text-center">
                <i class="fa-solid fa-circle-notch fa-spin text-3xl text-datamind-primary mb-3"></i>
                <p class="text-xs font-semibold text-slate-600">Inspeccionando todas las tablas y columnas en tiempo real...</p>
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
            explorerSubtitle.textContent = `Motor ${payload.connection.engine.toUpperCase()} en ${payload.connection.host}:${payload.connection.port} — Selecciona las tablas que deseas enseñarle al asistente.`;
            explorerStatsBadge.textContent = `${payload.total_tables} tablas detectadas • ${payload.vectorized_count} vectorizadas`;
            explorerStatsBadge.classList.remove('hidden');

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
            if (!filterText) return true;
            return t.table_name.toLowerCase().includes(filterText) ||
                   (t.column_names_preview || '').toLowerCase().includes(filterText);
        });

        if (filtered.length === 0) {
            tablesListBody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                        No se encontraron tablas que coincidan con "${escapeHtml(filterText)}".
                    </td>
                </tr>
            `;
            return;
        }

        tablesListBody.innerHTML = filtered.map(t => {
            const isChecked = selectedTableNames.has(t.table_name);
            const statusBadge = t.is_vectorized
                ? `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                       <i class="fa-solid fa-check text-[10px]"></i> Vectorizada
                   </span>`
                : `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500">
                       Pendiente
                   </span>`;

            return `
                <tr class="table-row-item hover:bg-datamind-light/20 transition cursor-pointer ${isChecked ? 'bg-datamind-light/25' : ''}"
                    data-table-name="${escapeHtml(t.table_name)}">
                    <td class="py-2.5 px-4 text-center">
                        <input type="checkbox" class="chk-table-item h-4 w-4 rounded accent-[#0476D9] cursor-pointer"
                            value="${escapeHtml(t.table_name)}" ${isChecked ? 'checked' : ''}>
                    </td>
                    <td class="py-2.5 px-4 font-mono font-bold text-datamind-dark">${escapeHtml(t.table_name)}</td>
                    <td class="py-2.5 px-4 text-center">
                        <span class="px-2 py-0.5 rounded bg-datamind-neutral text-slate-700 font-semibold text-[11px]">
                            ${t.columns_count} cols
                        </span>
                    </td>
                    <td class="py-2.5 px-4 text-slate-500 font-mono text-[11px] max-w-md truncate" title="${escapeHtml(t.column_names_preview)}">
                        ${escapeHtml(t.column_names_preview)}
                    </td>
                    <td class="py-2.5 px-4 text-center">${statusBadge}</td>
                </tr>
            `;
        }).join('');

        // Eventos de click en fila y checkbox
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

    function toggleTableSelection(tableName, checked, rowElem) {
        if (checked) {
            selectedTableNames.add(tableName);
            rowElem?.classList.add('bg-datamind-light/25');
        } else {
            selectedTableNames.delete(tableName);
            rowElem?.classList.remove('bg-datamind-light/25');
        }
        updateVectorizeButtonState();
    }

    function toggleVisibleCheckboxes(checked) {
        const filterText = tableSearchInput?.value.trim().toLowerCase() || '';
        allDetectedTables.forEach(t => {
            if (!filterText ||
                t.table_name.toLowerCase().includes(filterText) ||
                (t.column_names_preview || '').toLowerCase().includes(filterText)) {
                if (checked) {
                    selectedTableNames.add(t.table_name);
                } else {
                    selectedTableNames.delete(t.table_name);
                }
            }
        });
        renderTablesRows(filterText);
        updateVectorizeButtonState();
    }

    function updateVectorizeButtonState() {
        const count = selectedTableNames.size;
        if (btnVectorizeText) {
            btnVectorizeText.textContent = `Vectorizar Seleccionadas (${count})`;
        }
        if (btnVectorizeSelected) {
            btnVectorizeSelected.disabled = count === 0;
            if (count > 0) {
                btnVectorizeSelected.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btnVectorizeSelected.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    async function handleVectorizeSelected() {
        if (!currentConnectionId || selectedTableNames.size === 0) return;

        const tablesArray = Array.from(selectedTableNames);
        btnVectorizeSelected.disabled = true;
        btnVectorizeText.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Vectorizando ${tablesArray.length} tablas en pgvector...`;

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
            updateVectorizeButtonState();
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
