/**
 * DataMind AI - Selector paginado de tablas vectorizadas (10 en 10) con búsqueda por nombre.
 *
 * Uso: <div data-table-picker data-placeholder="..."><input type="hidden" id="miCampo"></div>
 *      const picker = TablePicker.init(container, { getConnectionId, onSelect });
 * El <input type="hidden"> conserva el valor elegido, por lo que `input.value` sigue funcionando.
 */
(function () {
    'use strict';

    const PAGE_SIZE = 10;

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function init(container, opts) {
        const options = opts || {};
        const hidden = container.querySelector('input[type="hidden"]');
        const placeholder = container.getAttribute('data-placeholder') || 'Selecciona una tabla...';

        container.classList.add('relative');
        container.insertAdjacentHTML('beforeend', `
            <button type="button" class="tp-trigger w-full px-2.5 py-1.5 border border-slate-200 bg-white text-xs font-mono text-left flex items-center justify-between gap-2 rounded-none cursor-pointer">
                <span class="tp-label truncate text-slate-400">${esc(placeholder)}</span>
                <i class="fa-solid fa-chevron-down text-[9px] text-slate-400"></i>
            </button>
            <div class="tp-panel hidden absolute left-0 right-0 top-full mt-0.5 z-30 bg-white border border-slate-300 shadow-xl">
                <div class="p-1.5 border-b border-slate-100">
                    <input type="text" class="tp-search w-full px-2 py-1 border border-slate-200 text-xs rounded-none focus:outline-hidden" placeholder="Buscar por nombre de tabla...">
                </div>
                <div class="tp-list max-h-56 overflow-y-auto"></div>
                <div class="tp-footer hidden p-1.5 border-t border-slate-100 text-center">
                    <button type="button" class="tp-more text-[11px] font-bold text-datamind-primary hover:underline cursor-pointer">Cargar más</button>
                </div>
            </div>
        `);

        const trigger = container.querySelector('.tp-trigger');
        const label = container.querySelector('.tp-label');
        const panel = container.querySelector('.tp-panel');
        const search = container.querySelector('.tp-search');
        const list = container.querySelector('.tp-list');
        const footer = container.querySelector('.tp-footer');
        const moreBtn = container.querySelector('.tp-more');

        let page = 1;
        let hasMore = false;
        let loading = false;
        let debounce = null;
        let requestSeq = 0;

        function setLoading(on) {
            loading = on;
            moreBtn.disabled = on;
            moreBtn.textContent = on ? 'Cargando...' : 'Cargar más';
        }

        async function load(reset) {
            const connId = options.getConnectionId ? options.getConnectionId() : '';
            if (reset) {
                page = 1;
                list.innerHTML = '';
            }
            if (!connId) {
                list.innerHTML = '<div class="px-3 py-3 text-xs text-slate-400">Selecciona primero una base de datos.</div>';
                footer.classList.add('hidden');
                return;
            }
            const seq = ++requestSeq;
            setLoading(true);
            if (reset) list.innerHTML = '<div class="px-3 py-3 text-xs text-slate-400"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Cargando tablas...</div>';

            try {
                const qs = new URLSearchParams({
                    connection_id: connId,
                    page: String(page),
                    per_page: String(PAGE_SIZE),
                    search: search.value.trim(),
                });
                const res = await fetch(`/training/vectorized-tables?${qs}`, { headers: { Accept: 'application/json' } });
                const json = await res.json();
                if (seq !== requestSeq) return; // respuesta obsoleta
                if (!res.ok || !json.success) throw new Error(json.message || 'No se pudieron cargar las tablas.');

                if (reset) list.innerHTML = '';
                if (json.data.length === 0 && reset) {
                    list.innerHTML = `<div class="px-3 py-3 text-xs text-slate-400">No hay tablas vectorizadas${search.value.trim() ? ' que coincidan' : ''}.
                        <a href="/connections/explorer?connection_id=${encodeURIComponent(connId)}" class="text-datamind-primary font-bold hover:underline">Vectorizar tablas</a></div>`;
                }
                json.data.forEach(t => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'tp-item w-full text-left px-3 py-1.5 text-xs font-mono hover:bg-slate-100 cursor-pointer';
                    btn.textContent = t.table_name;
                    btn.addEventListener('click', () => select(t.table_name, true));
                    list.appendChild(btn);
                });
                hasMore = !!json.has_more;
                footer.classList.toggle('hidden', !hasMore);
            } catch (err) {
                if (seq !== requestSeq) return;
                list.innerHTML = `<div class="px-3 py-3 text-xs text-red-600">${esc(err.message)}</div>`;
                footer.classList.add('hidden');
            } finally {
                if (seq === requestSeq) setLoading(false);
            }
        }

        function open() {
            panel.classList.remove('hidden');
            search.focus();
            if (!list.children.length) load(true);
        }

        function close() {
            panel.classList.add('hidden');
        }

        function select(name, notify) {
            if (hidden) hidden.value = name || '';
            label.textContent = name || placeholder;
            label.classList.toggle('text-slate-400', !name);
            label.classList.toggle('text-slate-800', !!name);
            close();
            if (notify && options.onSelect) options.onSelect(name);
        }

        trigger.addEventListener('click', () => (panel.classList.contains('hidden') ? open() : close()));
        moreBtn.addEventListener('click', () => {
            if (loading || !hasMore) return;
            page += 1;
            load(false);
        });
        search.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => load(true), 300);
        });
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) close();
        });

        const api = {
            getValue: () => (hidden ? hidden.value : ''),
            setValue: (name) => select(name, false),
            // Reinicia selección y lista (p. ej. al guardar o cambiar de conexión)
            reset: () => {
                select('', false);
                search.value = '';
                list.innerHTML = '';
                footer.classList.add('hidden');
                requestSeq++;
            },
        };
        container.tablePicker = api;
        return api;
    }

    window.TablePicker = { init };
})();
