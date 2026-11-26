<x-app-layout
    title="Conceptos de Conocimiento"
    ecosystem="vectorization"
    ecosystemLabel="Vectorización & Bases de Datos"
    ecosystemIcon="fa-database"
    activeModule="Conceptos"
    activeContext="Configuración / Conceptos"
    :fullHeight="true"
>
    <main class="flex-1 w-full flex overflow-hidden rounded-none">
        <aside class="hidden lg:flex flex-col w-80 shrink-0 bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar rounded-none">
            <x-ecosystem-sidebar ecosystemKey="vectorization" />
        </aside>

        <section id="training-main-content" class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden">
            <!-- Barra Superior de la Vista -->
            <div class="px-5 py-3 border-b border-slate-200 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 bg-datamind-primary text-white flex items-center justify-center font-bold text-base rounded-none shadow-none">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-slate-900 leading-tight">Conceptos de Conocimiento</h1>
                        <p class="text-[11px] text-slate-500">Haz clic en cualquier concepto para editarlo y re-vectorizarlo en pgvector.</p>
                    </div>
                </div>
            </div>

            <div class="flex-1 p-6 overflow-y-auto custom-scrollbar space-y-6 bg-slate-50/30">
                <div id="conceptsCardsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="col-span-full py-12 text-center text-xs text-slate-400">Cargando conceptos...</div>
                </div>
            </div>
        </section>
    </main>

    <!-- MODAL: EDITAR Y RE-VECTORIZAR CONCEPTO -->
    <div id="modalEditConcept" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs items-center justify-center p-4">
        <div class="bg-white border border-slate-200 w-full max-w-md rounded-none shadow-xl">
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-pen-to-square mr-1 text-datamind-primary"></i> Editar Concepto</h3>
                <button type="button" id="btnCloseEditConcept" class="text-slate-400 hover:text-slate-700 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="formEditConcept" class="p-4 space-y-3">
                <input type="hidden" id="editConceptId">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nombre corto *</label>
                    <input type="text" id="editConceptShortName" maxlength="50" required class="w-full px-3 py-1.5 border border-slate-300 text-xs rounded-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nombre largo *</label>
                    <input type="text" id="editConceptLongName" maxlength="150" required class="w-full px-3 py-1.5 border border-slate-300 text-xs rounded-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Descripción *</label>
                    <textarea id="editConceptDescription" rows="4" required class="w-full px-3 py-1.5 border border-slate-300 text-xs rounded-none"></textarea>
                </div>
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" id="btnCancelEditConcept" class="px-3 py-1.5 bg-white border border-slate-300 text-xs font-semibold text-slate-700 rounded-none cursor-pointer">Cancelar</button>
                    <button type="submit" id="btnUpdateConcept" class="px-3 py-1.5 bg-datamind-primary text-white text-xs font-bold rounded-none cursor-pointer">Guardar y Re-vectorizar</button>
                </div>
            </form>
        </div>
    </div>

    <x-slot:scripts>
        <script>
            (function () {
                let allConcepts = [];
                const grid = document.getElementById('conceptsCardsGrid');
                const modal = document.getElementById('modalEditConcept');

                function escapeHtml(s) {
                    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                }

                async function loadConcepts() {
                    try {
                        const res = await fetch('/training/concepts', { headers: { 'Accept': 'application/json' } });
                        allConcepts = await res.json();
                        if (!Array.isArray(allConcepts) || !allConcepts.length) {
                            grid.innerHTML = '<div class="col-span-full py-12 text-center text-xs text-slate-400">No hay conceptos registrados aún.</div>';
                            return;
                        }
                        grid.innerHTML = allConcepts.map((c, idx) => `
                            <div class="concept-card p-4 bg-white border border-slate-200 hover:border-datamind-primary transition cursor-pointer flex flex-col justify-between shadow-2xs" data-idx="${idx}">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="text-[10px] font-bold px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono">${escapeHtml(c.short_name)}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">pgvector</span>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-800 mb-1">${escapeHtml(c.long_name)}</h4>
                                    <p class="text-[11px] text-slate-600 line-clamp-3">${escapeHtml(c.description)}</p>
                                </div>
                                <div class="pt-2 mt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                                    <span>${c.created_at ? new Date(c.created_at).toLocaleDateString() : ''}</span>
                                    <span class="text-datamind-primary font-semibold">Editar <i class="fa-solid fa-pen ml-1"></i></span>
                                </div>
                            </div>
                        `).join('');

                        grid.querySelectorAll('.concept-card').forEach(card => {
                            card.addEventListener('click', () => {
                                const c = allConcepts[parseInt(card.getAttribute('data-idx'), 10)];
                                if (!c) return;
                                document.getElementById('editConceptId').value = c.id;
                                document.getElementById('editConceptShortName').value = c.short_name;
                                document.getElementById('editConceptLongName').value = c.long_name;
                                document.getElementById('editConceptDescription').value = c.description;
                                modal.classList.remove('hidden');
                            });
                        });
                    } catch (e) {
                        grid.innerHTML = '<div class="col-span-full py-12 text-center text-xs text-red-500">Error al cargar conceptos.</div>';
                    }
                }

                ['btnCloseEditConcept', 'btnCancelEditConcept'].forEach(id => {
                    document.getElementById(id)?.addEventListener('click', () => modal.classList.add('hidden'));
                });

                document.getElementById('formEditConcept')?.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const id = document.getElementById('editConceptId').value;
                    const btn = document.getElementById('btnUpdateConcept');
                    btn.disabled = true;
                    try {
                        const res = await fetch(`/training/concepts/${encodeURIComponent(id)}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify({
                                short_name: document.getElementById('editConceptShortName').value.trim(),
                                long_name: document.getElementById('editConceptLongName').value.trim(),
                                description: document.getElementById('editConceptDescription').value.trim()
                            })
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.detail || 'Error al actualizar');
                        modal.classList.add('hidden');
                        await loadConcepts();
                        if (window.toastr) window.toastr.success('Concepto actualizado y re-vectorizado');
                    } catch (err) {
                        if (window.toastr) window.toastr.error(err.message);
                    } finally {
                        btn.disabled = false;
                    }
                });

                document.addEventListener('DOMContentLoaded', loadConcepts);
            })();
        </script>
    </x-slot:scripts>
    @include('settings.partials.settings-spa-nav')
</x-app-layout>
