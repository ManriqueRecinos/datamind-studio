/**
 * DataMind AI - Selectores guiados para la plantilla "Relaciones JOIN".
 * Encadena: Tabla Origen → Columna Foránea → Tabla Maestra → Campo Legible,
 * usando solo tablas vectorizadas (requiere table_picker.js).
 *
 * RelationSelectors.init({
 *   sourcePickerEl, targetPickerEl,        // contenedores [data-table-picker]
 *   sourceColSelect, labelColSelect,       // <select>
 *   getConnectionId, notify                // funciones
 * }) → { reset(), applyExample(), getTargetPk() }
 */
(function () {
    'use strict';

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function init(cfg) {
        const { sourcePickerEl, targetPickerEl, sourceColSelect, labelColSelect } = cfg;
        const getConnId = cfg.getConnectionId || (() => '');
        const notify = cfg.notify || (() => {});
        if (!window.TablePicker || !sourcePickerEl || !targetPickerEl || !sourceColSelect || !labelColSelect) return null;

        let targetPk = 'id';
        let sourceColumns = [];

        const setSelect = (select, text, disabled) => {
            select.innerHTML = `<option value="">${esc(text)}</option>`;
            select.disabled = disabled;
        };

        async function fetchColumns(table) {
            const qs = new URLSearchParams({ connection_id: getConnId(), table });
            const res = await fetch(`/training/vectorized-tables/columns?${qs}`, { headers: { Accept: 'application/json' } });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'No se pudieron cargar las columnas.');
            return json;
        }

        function fillSelect(select, columns, placeholder, preselect) {
            select.innerHTML = `<option value="">${esc(placeholder)}</option>` + columns.map(c => {
                const tag = c.is_fk ? ` → ${c.references?.table || 'FK'}` : '';
                return `<option value="${esc(c.name)}">${esc(c.name + tag)}</option>`;
            }).join('');
            select.disabled = false;
            if (preselect && columns.some(c => c.name === preselect)) select.value = preselect;
        }

        async function onSourceTable(table) {
            setSelect(sourceColSelect, 'Cargando columnas...', true);
            try {
                const info = await fetchColumns(table);
                sourceColumns = info.columns;
                const rank = c => (c.is_fk ? 0 : (/_id$/i.test(c.name) ? 1 : 2));
                fillSelect(sourceColSelect, [...info.columns].sort((a, b) => rank(a) - rank(b)), 'Elige la columna foránea', null);
            } catch (err) {
                setSelect(sourceColSelect, 'Error al cargar columnas', true);
                notify('error', err.message);
            }
        }

        async function onTargetTable(table, forcedPk) {
            setSelect(labelColSelect, 'Cargando columnas...', true);
            try {
                const info = await fetchColumns(table);
                targetPk = forcedPk || info.primary_key || 'id';
                fillSelect(labelColSelect, info.columns, 'Elige el campo legible', info.label_suggestion);
            } catch (err) {
                setSelect(labelColSelect, 'Error al cargar columnas', true);
                notify('error', err.message);
            }
        }

        const sourcePicker = TablePicker.init(sourcePickerEl, { getConnectionId: getConnId, onSelect: onSourceTable });
        const targetPicker = TablePicker.init(targetPickerEl, { getConnectionId: getConnId, onSelect: (t) => onTargetTable(t) });

        // Si la columna elegida es una FK real, preseleccionar la tabla maestra y su llave
        sourceColSelect.addEventListener('change', async () => {
            const col = sourceColumns.find(c => c.name === sourceColSelect.value);
            if (col && col.is_fk && col.references && col.references.table) {
                targetPicker.setValue(col.references.table);
                await onTargetTable(col.references.table, col.references.column);
            }
        });

        function reset() {
            targetPk = 'id';
            sourceColumns = [];
            sourcePicker.reset();
            targetPicker.reset();
            setSelect(sourceColSelect, 'Primero elige la tabla origen', true);
            setSelect(labelColSelect, 'Primero elige la tabla maestra', true);
        }

        async function applyExample() {
            sourcePicker.setValue('users');
            await onSourceTable('users');
            if ([...sourceColSelect.options].some(o => o.value === 'id')) sourceColSelect.value = 'id';
            targetPicker.setValue('roles');
            await onTargetTable('roles');
            if ([...labelColSelect.options].some(o => o.value === 'name')) labelColSelect.value = 'name';
        }

        return { reset, applyExample, getTargetPk: () => targetPk };
    }

    window.RelationSelectors = { init };
})();
